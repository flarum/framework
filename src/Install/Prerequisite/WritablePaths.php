<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WritablePaths implements PrerequisiteInterface
{
    /**
     * @var Collection
     */
    private $paths;

    private $wildcards = [];

    public function __construct(array $paths)
    {
        $this->paths = $this->normalize($paths);
    }

    public function problems(): Collection
    {
        return $this->getMissingPaths()
            ->concat($this->getNonWritablePaths());
    }

    private function getMissingPaths(): Collection
    {
        return $this->paths
            ->reject(function ($path) {
                return file_exists($path);
            })->map(function ($path) {
                return [
                    'message' => 'The '.$this->getAbsolutePath($path).' directory doesn\'t exist',
                    'detail' => 'This directory is necessary for the installation. Please create the folder.',
                ];
            });
    }

    private function getNonWritablePaths(): Collection
    {
        return $this->paths
            ->filter(function ($path) {
                return file_exists($path) && ! is_writable($path);
            })->map(function ($path, $index) {
                return [
                    'message' => 'The '.$this->getAbsolutePath($path).' directory is not writable.',
                    'detail' => 'Please make sure your web server/PHP user has write access to this directory'.(in_array($index, $this->wildcards) ? ' and its contents' : '').'. Read the <a href="https://docs.flarum.org/install/#folder-ownership">installation documentation</a> for a detailed explanation and steps to resolve this error.'
                ];
            });
    }

    private function getAbsolutePath($path)
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];

        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return (substr($path, 0, 1) == '/' ? '/' : '').implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    private function normalize(array $paths): Collection
    {
        return (new Collection($paths))
            ->map(function ($path, $index) {
                if (Str::endsWith($path, '/*')) {
                    $this->wildcards[] = $index;
                    $path = substr($path, 0, -2);
                }

                return $path;
            });
    }
}
