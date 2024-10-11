<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Composer;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ComposerJson
{
    protected ?array $initialJson = null;

    public function __construct(
        protected Paths $paths,
        protected Filesystem $filesystem,
        protected ExtensionManager $extensions
    ) {
    }

    public function require(string $packageName, string $version): void
    {
        $composerJson = $this->get();

        if (! str_contains($packageName, '*')) {
            $composerJson['require'][$packageName] = $version;
        } else {
            foreach ($composerJson['require'] as $p => $v) {
                if ($version === '*@dev') {
                    continue;
                }

                // Only extensions can all be set to * versioning.
                if (! $this->extensions->getExtension(Extension::nameToId($packageName))) {
                    continue;
                }

                $wildcardPackageName = str_replace('\*', '.*', preg_quote($packageName, '/'));

                if (Str::of($p)->test("/($wildcardPackageName)/")) {
                    $composerJson['require'][$p] = $version;
                }
            }
        }

        $this->set($composerJson);
    }

    public function revert(): void
    {
        $this->set($this->initialJson);
    }

    protected function getComposerJsonPath(): string
    {
        return $this->paths->base.'/composer.json';
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get(): array
    {
        $json = json_decode($this->filesystem->get($this->getComposerJsonPath()), true);

        if (! $this->initialJson) {
            $this->initialJson = $json;
        }

        return $json;
    }

    public function set(array $json): void
    {
        $this->filesystem->put($this->getComposerJsonPath(), json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
