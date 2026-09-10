<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Compiler\Source\StringSource;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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

    private function secretFile(): string
    {
        $path = sys_get_temp_dir().'/flarum-less-secret.txt';
        file_put_contents($path, 'SECRET-LESS-FILE-READ db_password=hunter2');

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
}
