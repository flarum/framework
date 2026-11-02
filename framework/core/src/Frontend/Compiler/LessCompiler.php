<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Source\FileSource;
use Illuminate\Support\Collection;
use Less_Exception_Parser;
use Less_Parser;
use Less_Tree;

/**
 * @internal
 */
class LessCompiler extends RevisionCompiler
{
    protected string $cacheDir;
    protected array $importDirs = [];
    protected array $customFunctions = [];
    protected ?Collection $lessImportOverrides = null;
    protected ?Collection $fileSourceOverrides = null;
    protected ?string $fontsDir = null;

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * The directory holding the webfonts that get published to `assets/fonts`.
     * Used to revision the font URLs emitted into the compiled CSS.
     */
    public function setFontsDir(?string $fontsDir): void
    {
        $this->fontsDir = $fontsDir;
    }

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function getImportDirs(): array
    {
        return $this->importDirs;
    }

    public function setImportDirs(array $importDirs): void
    {
        $this->importDirs = $importDirs;
    }

    public function setLessImportOverrides(array $lessImportOverrides): void
    {
        $this->lessImportOverrides = new Collection($lessImportOverrides);
    }

    public function setFileSourceOverrides(array $fileSourceOverrides): void
    {
        $this->fileSourceOverrides = new Collection($fileSourceOverrides);
    }

    public function setCustomFunctions(array $customFunctions): void
    {
        $this->customFunctions = $customFunctions;
    }

    /**
     * Resolve an `@import` against the permitted directories, and refuse it
     * otherwise.
     *
     * Raw LESS can read any file the process can reach — `@import (inline)`
     * pastes it in verbatim, and `data-uri()` encodes it into the stylesheet —
     * and custom LESS is written by an administrator but compiled into the
     * *public* stylesheet. That has to be contained here rather than by
     * rejecting spellings in the setting: less.php's import directive is
     * matched as `@import?`, so `@impor` parses identically, and any blocklist
     * only ever chases the parser's grammar.
     *
     * Registered as the sole entry in less.php's `import_dirs`. A closure there
     * is asked to resolve the path and may decline by returning null — but for
     * a local path declining lets less.php fall back to reading the raw path,
     * so an import we will not resolve has to throw instead.
     *
     * @return array{0: string, 1: null}|null
     *
     * @throws \Less_Exception_Parser
     */
    protected function containImports(string $path): ?array
    {
        // A remote stylesheet is not ours to resolve, so decline it and let
        // less.php emit the directive for the browser. Declining, rather than
        // throwing, is what keeps the ordinary `@import url(...)` webfont case
        // working -- this callback is consulted for every import, and throwing
        // here would refuse those too.
        //
        // The scheme is required. A scheme-less `//host/x` cannot be told apart
        // from a local path: `//etc/passwd` names the same file as
        // `/etc/passwd`, so a `(https?:)?//` guard matches a purely local path.
        // That matters because declining does not end the import -- less.php
        // falls back to the raw path and, for an `(inline)` import, reads it
        // with file_get_contents(), inlining the file into the public
        // stylesheet. The inline flag is not visible from here (less.php passes
        // this callback the filename alone), so the path string has to be
        // conclusive on its own.
        //
        // The cost is that a protocol-relative import is no longer accepted.
        if (preg_match('#^https?://#i', $path) === 1) {
            return null;
        }

        foreach ($this->importDirs as $dir => $uri) {
            // An entry may be given as a bare path with a numeric key, or as
            // `path => uri` for URL rewriting.
            $dir = is_string($dir) ? $dir : $uri;

            if (! is_string($dir) || $dir === '') {
                continue;
            }

            $root = realpath($dir);

            if ($root === false) {
                continue;
            }

            $resolved = realpath($root.'/'.ltrim($path, '/\\'));

            // realpath() has followed `..` and any symlink, so a path that
            // still starts with the directory really is inside it.
            if ($resolved !== false && str_starts_with($resolved, $root.DIRECTORY_SEPARATOR)) {
                return [$resolved, null];
            }
        }

        throw new Less_Exception_Parser(
            sprintf('Importing "%s" is not allowed.', $path)
        );
    }

    /**
     * @throws \Less_Exception_Parser
     */
    protected function compile(array $sources): string
    {
        if (! count($sources)) {
            return '';
        }

        if (! empty($this->settings->get('custom_less_error'))) {
            unset($sources['custom_less']);
        }

        $maxNestingLevel = ini_get('xdebug.max_nesting_level');

        ini_set('xdebug.max_nesting_level', '200');

        try {
            $parser = new Less_Parser([
                'compress' => true,
                'strictMath' => false,
                // Resolve every `@import` ourselves, so one cannot reach a file
                // outside the directories below. See containImports().
                'import_dirs' => [$this->containImports(...)],
                // No `cache_dir`: less.php's per-import parse cache costs more
                // than it saves. A cached tree unserializes with a private copy
                // of `currentFileInfo` on every node, around five times the
                // memory of the tree the parser builds, and reading it back is
                // slower than parsing the file again. Compiled output is cached
                // as an asset already, so a parse only happens on a rebuild.
            ]);

            if ($this->fileSourceOverrides) {
                $sources = $this->overrideSources($sources);
            }

            foreach ($sources as $source) {
                if ($source instanceof FileSource) {
                    // If we have import overrides, parse the file content and apply them
                    if ($this->lessImportOverrides && $this->lessImportOverrides->isNotEmpty()) {
                        $content = file_get_contents($source->getPath());
                        $content = $this->applyImportOverridesToContent($content);
                        // Pass the original file path to maintain proper import resolution context
                        $parser->parse($content, $source->getPath());
                    } else {
                        $parser->parseFile($source->getPath());
                    }
                } else {
                    $parser->parse($source->getContent());
                }
            }

            foreach ($this->customFunctions as $name => $callback) {
                $parser->registerFunction($name, $callback);
            }

            try {
                $compiled = $this->finalize($parser->getCss());

                if (isset($sources['custom_less']) && $this->settings->get('custom_less_error')) {
                    $this->settings->delete('custom_less_error');
                }

                return $compiled;
                // Less_Exception_Compiler extends Less_Exception_Parser, so this
                // covers both: a refused import (containImports()) raises the
                // latter, and must drop the offending custom LESS like any other
                // bad value rather than taking the whole forum down with it.
            } catch (Less_Exception_Parser $e) {
                if (isset($sources['custom_less'])) {
                    unset($sources['custom_less']);

                    $compiled = $this->compile($sources);

                    $this->settings->set('custom_less_error', $e->getMessage());

                    return $compiled;
                }

                throw $e;
            }
        } finally {
            // less.php keeps the last parser, with its whole tree, in a static,
            // so one compile's memory is still held when the next one begins.
            // An empty parser in its place lets the tree go.
            Less_Tree::$parse = new Less_Parser();

            if ($maxNestingLevel !== false) {
                ini_set('xdebug.max_nesting_level', $maxNestingLevel);
            }
        }
    }

    /**
     * Point font URLs at the published `assets/fonts` directory, and stamp each
     * with a revision derived from the font file itself.
     *
     * The stylesheet is already cache-busted (`forum.css?v=<rev>`), but the font
     * URLs inside it were not. On a FontAwesome major upgrade every browser
     * therefore picked up the new CSS immediately while continuing to serve the
     * *previous* font file from cache — same filename, same URL, long max-age.
     * The new CSS asks for codepoints the old font doesn't contain, so every
     * icon rendered as a placeholder box until that cache entry happened to
     * expire. Revisioning the URL means a changed font is always a new URL, for
     * browser and CDN caches alike.
     *
     * Because the asset revision is a hash of this compiled output, a font
     * change also moves the stylesheet's own revision — so connected clients get
     * the usual "reload for the new version" prompt without any extra wiring.
     */
    protected function finalize(string $parsedCss): string
    {
        return preg_replace_callback(
            '~url\("\.\./webfonts/([^"?#]+)([^"]*)"\)~',
            function (array $matches): string {
                [, $file, $suffix] = $matches;

                $revision = $this->fontRevision($file);

                // Preserve any existing query/fragment (e.g. `#iefix`), and
                // don't add a second `?` if one is already there.
                if ($revision !== null) {
                    $suffix .= (str_contains($suffix, '?') ? '&' : '?')."v=$revision";
                }

                return 'url("./fonts/'.$file.$suffix.'")';
            },
            $parsedCss
        ) ?? $parsedCss;
    }

    protected function fontRevision(string $file): ?string
    {
        return static::fontRevisionIn($this->fontsDir, $file);
    }

    /**
     * A short hash of a webfont's contents, or null when it can't be read —
     * fonts are published separately, so a compile must never fail just because
     * the directory isn't there yet.
     *
     * This is the one place the revision is worked out: anything else that links
     * to a bundled font (the font preloads) goes through
     * {@see \Flarum\Frontend\Assets::fontRevision()}, which calls this with the
     * same directory, so its URL is the one the stylesheet requests.
     */
    public static function fontRevisionIn(?string $fontsDir, string $file): ?string
    {
        if ($fontsDir === null) {
            return null;
        }

        // Defend the filesystem read against anything unexpected in the URL.
        if (basename($file) !== $file) {
            return null;
        }

        $path = $fontsDir.'/'.$file;

        if (! file_exists($path)) {
            return null;
        }

        $hash = @hash_file('xxh128', $path);

        return $hash === false ? null : $hash;
    }

    /**
     * Apply import overrides by replacing @import statements with inline content.
     */
    private function applyImportOverridesToContent(string $content): string
    {
        foreach ($this->lessImportOverrides as $override) {
            $file = $override['file'];
            $fileWithoutExt = preg_replace('/\.less$/i', '', $file);
            $quotedFile = preg_quote($fileWithoutExt, '/');

            // Match @import "path" or @import 'path' (with or without .less extension)
            $pattern = '/@import\s+["\']'.$quotedFile.'(\.less)?["\'];?/i';

            if (preg_match($pattern, $content)) {
                // Read the override file content
                $overrideContent = file_get_contents($override['newFilePath']);

                // Replace the @import statement with the actual content
                $content = preg_replace(
                    $pattern,
                    '/* Flarum override: '.$file.' */'."\n".$overrideContent."\n".'/* End override */',
                    $content
                );
            }
        }

        return $content;
    }

    protected function overrideSources(array $sources): array
    {
        foreach ($sources as $source) {
            if ($source instanceof FileSource) {
                $basename = basename($source->getPath());
                $override = $this->fileSourceOverrides
                    ->where('file', $basename)
                    ->firstWhere('extensionId', $source->getExtensionId());

                if ($override) {
                    $source->setPath($override['newFilePath']);
                }
            }
        }

        return $sources;
    }
}
