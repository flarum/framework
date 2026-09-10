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
use Less_Cache;
use Less_Exception_Parser;
use Less_Parser;

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
     * is asked to resolve the path and may decline by returning null — but
     * declining lets less.php fall back to reading the raw path, so an import
     * we will not resolve has to throw instead.
     *
     * @return array{0: string, 1: null}
     *
     * @throws \Less_Exception_Parser
     */
    protected function containImports(string $path): array
    {
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
                'cache_dir' => $this->cacheDir,
                // Resolve every `@import` ourselves, so one cannot reach a file
                // outside the directories below. See containImports().
                'import_dirs' => [$this->containImports(...)],
                // less.php's built-in `serialize` cache writes each per-import
                // cache file non-atomically and reads it back with an unguarded
                // unserialize(). Concurrent compiles racing on the same file
                // leave trailing bytes, and the next reader then fatals on
                // "unserialize(): Extra data". Take over both sides of the cache
                // so reads treat corruption as a miss and writes are atomic.
                'cache_method' => 'callback',
                'cache_callback_get' => $this->readCache(...),
                'cache_callback_set' => $this->writeCache(...),
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
            if ($maxNestingLevel !== false) {
                ini_set('xdebug.max_nesting_level', $maxNestingLevel);
            }
        }
    }

    /**
     * Read a cached parse result for less.php. Returns the cached rules, or
     * null to signal a miss so less.php reparses the file.
     *
     * A corrupt or unreadable cache file (e.g. a partial write from a raced
     * compile) is treated as a miss rather than allowed to fatal on
     * unserialize()'s "Extra data" warning, which Flarum's error handler would
     * otherwise escalate to an uncaught exception.
     */
    protected function readCache(Less_Parser $parser, string $filePath, string $cacheFile): mixed
    {
        if (! is_file($cacheFile)) {
            return null;
        }

        $contents = @file_get_contents($cacheFile);

        if ($contents === false || $contents === '') {
            return null;
        }

        // A partial write leaves trailing bytes, so unserialize() emits an
        // "Extra data" warning. `@` alone is not enough: a custom error handler
        // (Sentry's, in production) runs regardless of suppression and would
        // escalate it to an uncaught exception — the very failure being fixed.
        // Swallow warnings for just this call so a corrupt file is a clean miss.
        set_error_handler(fn () => true);

        try {
            $cache = unserialize($contents);
        } catch (\Throwable) {
            $cache = false;
        } finally {
            restore_error_handler();
        }

        // A genuine `false` payload never occurs (rules are always an array),
        // so treat any falsy/failed result as a corrupt-or-empty miss.
        return $cache ?: null;
    }

    /**
     * Persist a parse result for less.php, writing atomically so a concurrent
     * reader never observes a half-written cache file: serialize to a temporary
     * file in the same directory, then rename() it into place (atomic on the
     * same filesystem).
     */
    protected function writeCache(Less_Parser $parser, string $filePath, string $cacheFile, mixed $rules): void
    {
        $dir = dirname($cacheFile);
        $tmp = @tempnam($dir, 'lesscache_');

        if ($tmp === false) {
            // Couldn't create a temp file (e.g. unwritable dir); skip caching
            // rather than risk a partial write. The next compile reparses.
            return;
        }

        if (@file_put_contents($tmp, serialize($rules)) === false) {
            @unlink($tmp);

            return;
        }

        if (! @rename($tmp, $cacheFile)) {
            @unlink($tmp);
        }

        $this->pruneCacheOnce();
    }

    /**
     * Prune expired cache files at most once per request. less.php's own GC
     * (Less_Cache::CleanCache) only runs from its high-level Less_Cache::Get()
     * API, which Flarum doesn't use — the direct Less_Parser path GC'd inline on
     * every serialize write instead. In callback mode neither fires, so without
     * this the directory would grow unbounded.
     *
     * We prune here rather than call CleanCache() because that method is
     * deprecated-internal, and hand-rolling the sweep lets us tolerate the
     * scandir/unlink race (a concurrent sweep removing the same aged file) by
     * simply suppressing the "No such file" and moving on. Files are removed by
     * mtime, matching less.php's own policy; a cache hit re-reads and is not
     * touched, so anything past the lifetime is genuinely stale.
     */
    protected function pruneCacheOnce(): void
    {
        static $pruned = [];

        if (isset($pruned[$this->cacheDir])) {
            return;
        }

        $pruned[$this->cacheDir] = true;

        $files = @glob($this->cacheDir.'/'.Less_Cache::$prefix.'*.lesscache');

        if (! $files) {
            return;
        }

        $cutoff = time() - Less_Cache::$gc_lifetime;

        foreach ($files as $file) {
            $mtime = @filemtime($file);

            if ($mtime !== false && $mtime < $cutoff) {
                // Tolerate a concurrent sweep having already removed it.
                @unlink($file);
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

    /**
     * A short hash of a webfont's contents, or null when it can't be read —
     * fonts are published separately, so a compile must never fail just because
     * the directory isn't there yet.
     */
    protected function fontRevision(string $file): ?string
    {
        if ($this->fontsDir === null) {
            return null;
        }

        // Defend the filesystem read against anything unexpected in the URL.
        if (basename($file) !== $file) {
            return null;
        }

        $path = $this->fontsDir.'/'.$file;

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
