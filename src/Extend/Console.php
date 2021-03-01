<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Console implements ExtenderInterface
{
    protected $addCommands = [];

    /**
     * Add a command to the console.
     *
     * @param string $command ::class attribute of command class, which must extend Flarum\Console\AbstractCommand
     */
    public function command($command)
    {
        $this->addCommands[] = $command;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.console.commands', function ($existingCommands) {
            return array_merge($existingCommands, $this->addCommands);
        });
    }
}
