<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Collection;

class WritablePaths implements PrerequisiteInterface
{
    protected $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function problems(): Collection
    {
        return $this->getMissingPaths()
            ->concat($this->getNonWritablePaths());
    }

    private function getMissingPaths(): Collection
    {
        return (new Collection($this->paths))
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
        return (new Collection($this->paths))
            ->filter(function ($path) {
                return file_exists($path) && ! is_writable($path);
            })->map(function ($path) {
                return [
                    'message' => 'The '.$this->getAbsolutePath($path).' directory is not writable.',
                    'detail' => 'Please chmod this directory'.($path !== public_path() ? ' and its contents' : '').' to 0775.'
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
}
