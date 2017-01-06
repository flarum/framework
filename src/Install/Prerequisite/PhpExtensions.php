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

class PhpExtensions extends AbstractPrerequisite
{
    protected $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function check()
    {
        foreach ($this->extensions as $extension) {
            $this->checkExtensions(explode('|', $extension));
        }
    }

    private function checkExtensions(array $extensions)
    {
        $loaded = array_reduce(
            $extensions,
            function ($previous, $extension) {
                return extension_loaded($extension) || $previous;
            },
            false
        );

        if (! $loaded) {
            $this->errors[] = [
                'message' => (count($extensions) > 1 ? 'One of the' : 'The')." PHP extension '".implode("' or '", $extensions)."' is required.",
            ];
        }
    }
}
