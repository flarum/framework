<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

use Flarum\Foundation\Paths;
use Illuminate\Filesystem\Filesystem;

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

    public function __construct(Paths $paths, Filesystem $filesystem)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    public function require(string $packageName, string $version): void
    {
        $composerJson = $this->getComposerJson();

        if (strpos($packageName, '*') === false) {
            $composerJson['require'][$packageName] = $version;
        } else {
            foreach ($composerJson['require'] as $p => $v) {
                if (preg_match(preg_quote(str_replace('*', '.*', $packageName), '/'), $p, $matches)) {
                    $composerJson['require'][$p] = $version;
                }
            }
        }

        $this->setComposerJson($composerJson);
    }

    public function revert(): void
    {
        $this->setComposerJson($this->initialJson);
    }

    protected function getComposerJsonPath(): string
    {
        return $this->paths->base . '/composer.json';
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getComposerJson(): array
    {
        $json = json_decode($this->filesystem->get($this->getComposerJsonPath()), true);

        if (! $this->initialJson) {
            $this->initialJson = $json;
        }

        return $json;
    }

    protected function setComposerJson(array $json): void
    {
        $this->filesystem->put($this->getComposerJsonPath(), json_encode($json, JSON_PRETTY_PRINT));
    }
}
