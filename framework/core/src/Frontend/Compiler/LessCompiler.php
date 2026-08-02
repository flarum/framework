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
use Less_Exception_Compiler;
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
                'import_dirs' => $this->importDirs,
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
            } catch (Less_Exception_Compiler $e) {
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
