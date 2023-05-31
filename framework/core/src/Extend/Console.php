<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Console implements ExtenderInterface
{
    protected array $addCommands = [];
    protected array $scheduled = [];

    /**
     * Add a command to the console.
     *
     * @param class-string<AbstractCommand> $command: ::class attribute of command class, which must extend \Flarum\Console\AbstractCommand.
     * @return self
     */
    public function command(string $command): self
    {
        $this->addCommands[] = $command;

        return $this;
    }

    /**
     * Schedule a command to run on an interval.
     *
     * @param class-string<AbstractCommand> $command: ::class attribute of command class, which must extend Flarum\Console\AbstractCommand.
     * @param (callable(\Illuminate\Console\Scheduling\Event $event): void)|class-string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Illuminate\Console\Scheduling\Event $event
     *
     * The callback should apply relevant methods to $event, and does not need to return anything.
     *
     * @see https://laravel.com/api/8.x/Illuminate/Console/Scheduling/Event.html
     * @see https://laravel.com/docs/8.x/scheduling#schedule-frequency-options
     * for more information on available methods and what they do.
     *
     * @param array $args An array of args to call the command with.
     * @return self
     */
    public function schedule(string $command, callable|string $callback, array $args = []): self
    {
        $this->scheduled[] = compact('args', 'callback', 'command');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $container->extend('flarum.console.commands', function ($existingCommands) {
            return array_merge($existingCommands, $this->addCommands);
        });

        $container->extend('flarum.console.scheduled', function ($existingScheduled) {
            return array_merge($existingScheduled, $this->scheduled);
        });
    }
}
