<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Frontend\Compiler\Source\FileSource;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Frontend\Compiler\Source\StringSource;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

/**
 * Raw LESS can read arbitrary files through `@import (inline)` and `data-uri()`,
 * so custom LESS — which an administrator supplies and which is compiled into
 * the public stylesheet — must not be able to reach outside Flarum's own LESS
 * directories.
 *
 * This was previously enforced by rejecting the literal strings `@import` and
 * `data-uri(`. The parser accepts `@impor` as well (its directive regex makes
 * the trailing `t` optional), so that spelling passed validation and inlined
 * the file. A blocklist can only ever chase the parser's grammar, so the file
 * system is taken away from the compiler instead.
 */
class CustomLessFileAccessTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Compiling the full stylesheet (and its source map) needs more than
        // the default limit an isolated test process gets.
        ini_set('memory_limit', '512M');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);
    }

    private ?string $documentRoot = null;

    protected function tearDown(): void
    {
        if ($this->documentRoot !== null) {
            $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
            $this->documentRoot = null;
        }

        parent::tearDown();
    }

    /**
     * The marker is also a valid LESS ruleset, so a `(less)` import that
     * reaches it compiles it into the stylesheet rather than failing to parse.
     */
    private function secretFile(): string
    {
        $path = sys_get_temp_dir().'/flarum-less-secret.txt';
        file_put_contents($path, '.SECRET-LESS-FILE-READ { content: "marker"; }');

        return $path;
    }

    private function saveCustomLess(string $less): int
    {
        return $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['custom_less' => $less],
        ]))->getStatusCode();
    }

    private function compiledForumCss(): string
    {
        $assets = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        // Drop any stylesheet compiled before the setting was saved, so this
        // reads the result of a real recompile rather than a cached file.
        foreach ($assets->allFiles() as $file) {
            if (str_ends_with($file, '.css')) {
                $assets->delete($file);
            }
        }

        $this->send($this->request('GET', '/'));

        $path = $assets->path('forum.css');

        return file_exists($path) ? file_get_contents($path) : '';
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fileReadingDirectives(): iterable
    {
        // The spelling the blocklist knew about.
        yield '@import (inline)' => ['@import (inline) "%s";'];
        // The same directive with the trailing `t` dropped: `@import?` in the
        // parser makes it optional, so this parses identically.
        yield '@impor (inline)' => ['@impor (inline) "%s";'];
        // A second read path, through a function rather than a directive.
        yield 'data-uri()' => ['body { background: data-uri("%s"); }'];
    }

    #[Test]
    #[DataProvider('fileReadingDirectives')]
    public function custom_less_cannot_read_a_file_outside_flarum(string $template)
    {
        $secret = $this->secretFile();

        $status = $this->saveCustomLess(sprintf($template, $secret));

        // Either refusing the setting or compiling it without the file is an
        // acceptable outcome; disclosing the file is not.
        $this->assertStringNotContainsString(
            'SECRET-LESS-FILE-READ',
            $this->compiledForumCss(),
            "a file outside Flarum was inlined into the public stylesheet (save returned $status)"
        );
    }

    #[Test]
    public function custom_less_cannot_traverse_out_of_an_allowed_directory()
    {
        $secret = $this->secretFile();

        $this->saveCustomLess(sprintf(
            '@impor (inline) "../../../../../../..%s";',
            $secret
        ));

        $this->assertStringNotContainsString('SECRET-LESS-FILE-READ', $this->compiledForumCss());
    }

    /**
     * How many `..` segments lead from less.php's entry path to `/`.
     *
     * less.php records the directory of the first file it parses as the entry
     * path, and every later source -- custom LESS included -- inherits it.
     */
    private function entryPathDepth(): int
    {
        $compiler = $this->app()->getContainer()->make('flarum.assets.forum')->makeCss();

        foreach ((new ReflectionMethod($compiler, 'getSources'))->invoke($compiler) as $source) {
            if ($source instanceof FileSource) {
                return substr_count(dirname(realpath($source->getPath())), '/');
            }
        }

        $this->fail('The forum stylesheet has no file source to take an entry path from.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fileImportingDirectives(): iterable
    {
        yield '@import (inline)' => ['@import (inline) "%s";'];
        yield '@impor (inline)' => ['@impor (inline) "%s";'];
        yield '@import (less)' => ['@import (less) "%s";'];
    }

    /**
     * less.php resolves an absolute import against the entry path *before* it
     * consults `import_dirs`, so containImports() never sees a path that climbs
     * out of it with `/..` segments. With exactly as many as reach `/`, the
     * import names any file on the server (more than that and less.php's own
     * path normalisation garbles it, fewer and it is refused).
     */
    #[Test]
    #[DataProvider('fileImportingDirectives')]
    public function custom_less_cannot_climb_out_of_the_entry_path(string $template)
    {
        $secret = $this->secretFile();

        $status = $this->saveCustomLess(sprintf(
            $template,
            '/'.str_repeat('../', $this->entryPathDepth()).ltrim($secret, '/')
        ));

        $this->assertStringNotContainsString(
            'SECRET-LESS-FILE-READ',
            $this->compiledForumCss(),
            "an absolute import climbed out of less.php's entry path (save returned $status)"
        );
    }

    /**
     * The other place less.php looks before `import_dirs`: in a web request, an
     * absolute import is tried against the document root, so `/../` reaches
     * the directory above it.
     */
    #[Test]
    #[DataProvider('fileImportingDirectives')]
    public function custom_less_cannot_climb_out_of_the_document_root(string $template)
    {
        $secret = $this->secretFile();

        $documentRoot = dirname($secret).'/flarum-less-document-root';
        is_dir($documentRoot) || mkdir($documentRoot);

        $this->documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

        $status = $this->saveCustomLess(sprintf($template, '/../'.basename($secret)));

        $this->assertStringNotContainsString(
            'SECRET-LESS-FILE-READ',
            $this->compiledForumCss(),
            "an absolute import climbed out of the document root (save returned $status)"
        );
    }

    /**
     * The functions that read a file resolve their path the same way imports
     * do, so the climbs above work through them too. `data-uri()` is refused
     * when the setting is saved, and the image functions are not, but the
     * compile is what has to hold: a value can also be stored without going
     * through that validation. So the setting is written directly here.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function fileReadingFunctions(): iterable
    {
        foreach (['entry path', 'document root'] as $route) {
            // Inlines the file. An SVG is URL-encoded rather than base64, so
            // the marker survives into the stylesheet as it is.
            yield "data-uri(), $route" => [$route, '.x { background: data-uri("%s"); }'];
            // Reads the SVG's width, so it discloses at least that much.
            yield "image-width(), $route" => [$route, '.x { width: image-width("%s"); }'];
        }
    }

    #[Test]
    #[DataProvider('fileReadingFunctions')]
    public function a_file_reading_function_cannot_climb_out_of_less_phps_lookups(string $route, string $template)
    {
        $target = sys_get_temp_dir().'/flarum-less-secret.svg';
        file_put_contents($target, '<svg xmlns="http://www.w3.org/2000/svg" width="98765" height="1"></svg>');

        if ($route === 'entry path') {
            $path = '/'.str_repeat('../', $this->entryPathDepth()).ltrim($target, '/');
        } else {
            $documentRoot = dirname($target).'/flarum-less-document-root';
            is_dir($documentRoot) || mkdir($documentRoot);

            $this->documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

            $path = '/../'.basename($target);
        }

        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)
            ->set('custom_less', sprintf($template, $path));

        $this->assertStringNotContainsString('98765', $this->compiledForumCss());
    }

    /**
     * Custom LESS must reach the compiler as a string, with no file of its own.
     *
     * less.php resolves a relative import against the importing file's own
     * directory *before* it consults `import_dirs`, so a source that carries a
     * file path can walk out of it with `../` without containImports() ever
     * being asked. Custom LESS escapes that only because it is added with
     * SourceCollector::addString() and parsed with no file URI.
     *
     * That is load-bearing rather than incidental: giving custom LESS a path —
     * writing it to a temp file, say — would silently reopen the file read this
     * test class covers. The relative traversal below is the behaviour that
     * would break, asserted here on its own so the reason is not lost.
     */
    #[Test]
    public function custom_less_is_compiled_without_a_file_of_its_own()
    {
        $sources = new SourceCollector();

        $sources->addString(fn () => '.marker { color: red; }', 'custom_less');

        $this->assertInstanceOf(
            StringSource::class,
            $sources->getSources()['custom_less'],
            'custom LESS must stay a StringSource: a source with a path can resolve '
            .'a relative import against its own directory, bypassing containImports()'
        );
    }

    /**
     * The companion to the assertion above, stated as behaviour: a relative
     * path must not escape, however the source is plumbed.
     */
    #[Test]
    public function custom_less_cannot_traverse_with_a_relative_path()
    {
        $secret = $this->secretFile();

        // Walk up further than any real directory nesting, so this lands at the
        // filesystem root wherever the compiler happens to be rooted.
        $this->saveCustomLess(sprintf(
            '@impor (inline) "%s%s";',
            str_repeat('../', 40),
            ltrim($secret, '/')
        ));

        $this->assertStringNotContainsString('SECRET-LESS-FILE-READ', $this->compiledForumCss());
    }

    /**
     * Containment stops the file read, but the administrator should still be
     * told at save time rather than silently getting a stylesheet with the
     * import dropped.
     *
     * The import cases are rejected by the trial compile, which turns
     * `LessCompiler::containImports()`'s refusal into a validation error —
     * not by a directive match in `ValidateCustomLess`, which would also
     * reject a perfectly safe remote import. `data-uri(` is still matched
     * there, since it never reaches the import machinery.
     */
    #[Test]
    public function saving_a_file_reading_directive_is_rejected()
    {
        $this->assertEquals(422, $this->saveCustomLess('@impor (inline) "/etc/passwd";'));
        $this->assertEquals(422, $this->saveCustomLess('@import (inline) "/etc/passwd";'));
        $this->assertEquals(422, $this->saveCustomLess('body { background: data-uri("/etc/passwd"); }'));
    }

    /**
     * The point of the change is containment, not breaking theming: ordinary
     * custom LESS must still compile into the stylesheet.
     */
    #[Test]
    public function ordinary_custom_less_still_compiles()
    {
        $status = $this->saveCustomLess('.custom-less-marker { color: #abcdef; }');

        $this->assertEquals(204, $status);
        $this->assertStringContainsString('.custom-less-marker', $this->compiledForumCss());
    }

    /**
     * Every shape less.php treats as remote, so narrowing the guard to just one
     * of them (requiring `url(`, say) cannot pass unnoticed.
     *
     * @return iterable<string, array{string}>
     */
    public static function remoteStylesheetImports(): iterable
    {
        // The line from the report, verbatim: single-quoted, and with several
        // `;`-separated weights inside the query string.
        yield 'single-quoted webfont' => ["@import url('https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap');"];
        // The same thing double-quoted.
        yield 'double-quoted webfont' => ['@import url("https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap");'];
        // Without the url() wrapper.
        yield '@import "..."' => ['@import "https://fonts.googleapis.com/css2?family=Raleway";'];
    }

    /**
     * A protocol-relative import is refused, and this records that as a
     * deliberate trade rather than an oversight.
     *
     * `//host/x` is indistinguishable from the local path `//host/x`, and the
     * decision has to be made from the path string alone, so allowing it would
     * re-open the file read that `custom_less_cannot_read_a_file_via_a_double_slash_path`
     * covers. Writing the scheme out in full is the workaround.
     */
    #[Test]
    public function a_protocol_relative_import_is_refused()
    {
        $status = $this->saveCustomLess('@import url("//fonts.googleapis.com/css2?family=Raleway");');

        $this->assertEquals(422, $status);
    }

    #[Test]
    #[DataProvider('remoteStylesheetImports')]
    public function custom_less_can_import_a_remote_stylesheet(string $less)
    {
        $status = $this->saveCustomLess($less);

        $this->assertEquals(204, $status);
        $this->assertStringContainsString('fonts.googleapis.com', $this->compiledForumCss());
    }

    /**
     * The decline is scoped to a real `https?://` scheme, so it cannot be used
     * to smuggle another scheme past containment.
     */
    #[Test]
    public function a_non_http_scheme_is_still_contained()
    {
        $secret = $this->secretFile();

        $this->saveCustomLess(sprintf('@impor "file://%s";', $secret));

        $this->assertStringNotContainsString('SECRET-LESS-FILE-READ', $this->compiledForumCss());
    }

    /**
     * Paths that a scheme-less `//` guard would wave through.
     *
     * A leading `//` is a valid absolute path on POSIX — `//etc/passwd` names
     * the same file as `/etc/passwd` — so a guard written as `(https?:)?//`
     * matches a purely local path. `containImports()` is consulted for EVERY
     * import, inline ones included, and declining does not end the import:
     * less.php falls back to the raw path (ImportVisitor::run()) and, for an
     * inline import, reads it with file_get_contents(). The result is inlined
     * into the world-readable forum.css.
     *
     * @return iterable<string, array{string}>
     */
    public static function doubleSlashLocalPaths(): iterable
    {
        yield '@import (inline)' => ['@import (inline) "//%s";'];
        // The trailing `t` is optional in less.php's directive regex.
        yield '@impor (inline)' => ['@impor (inline) "//%s";'];
        // Three slashes collapse to a valid path too.
        yield '@import (inline), three slashes' => ['@import (inline) "///%s";'];
    }

    /**
     * The cell the original guard missed: an inline import whose path starts
     * with `//`. Both halves of the earlier test matrix were covered — `//`
     * with a non-inline import, and `(inline)` with a single-slash path — but
     * not the two together, which is where the file read lives.
     */
    #[Test]
    #[DataProvider('doubleSlashLocalPaths')]
    public function custom_less_cannot_read_a_file_via_a_double_slash_path(string $template)
    {
        $secret = $this->secretFile();

        $this->saveCustomLess(sprintf($template, ltrim($secret, '/')));

        $this->assertStringNotContainsString(
            'SECRET-LESS-FILE-READ',
            $this->compiledForumCss(),
            'A `//`-prefixed path is local, not remote, and must not be read into the stylesheet.'
        );
    }

    /**
     * The same path shape through a LESS config variable, which is interpolated
     * as a variable value and so can close the declaration and append its own
     * directive.
     */
    #[Test]
    public function a_theme_colour_cannot_read_a_file_via_a_double_slash_path()
    {
        $secret = $this->secretFile();

        $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => [
                'theme_primary_color' => sprintf('#4D698E;@impor (inline) "//%s";', ltrim($secret, '/')),
            ],
        ]));

        $this->assertStringNotContainsString('SECRET-LESS-FILE-READ', $this->compiledForumCss());
    }
}
