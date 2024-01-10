<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Composer;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Flarum\ExtensionManager\Support\Util;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ComposerJson
{
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $initialJson;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(Paths $paths, Filesystem $filesystem, ExtensionManager $extensions)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
        $this->extensions = $extensions;
    }

    public function require(string $packageName, string $version): void
    {
        $composerJson = $this->get();

        if (strpos($packageName, '*') === false) {
            $composerJson['require'][$packageName] = $version;
        } else {
            foreach ($composerJson['require'] as $p => $v) {
                if ($version === '*@dev') {
                    continue;
                }

                // Only extensions can all be set to * versioning.
                if (! $this->extensions->getExtension(Util::nameToId($packageName))) {
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
