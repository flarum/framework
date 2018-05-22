<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

class WritablePaths extends AbstractPrerequisite
{
    protected $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function check()
    {
        foreach ($this->paths as $path) {
            if (! file_exists($path)) {
                $this->errors[] = [
                    'message' => 'The '.$this->getAbsolutePath($path).' directory doesn\'t exist',
                    'detail' => 'This directory is necessary for the installation. Please create the folder.',
                ];
            } elseif (! is_writable($path)) {
                $this->errors[] = [
                    'message' => 'The '.$this->getAbsolutePath($path).' directory is not writable.',
                    'detail' => 'Please chmod this directory'.($path !== public_path() ? ' and its contents' : '').' to 0775.'
                ];
            }
        }
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
