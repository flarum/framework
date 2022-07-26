<?php

namespace Flarum\Queue;

use Carbon\Carbon;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Console\RestartCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class QueueRestarter
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen([
            ClearingCache::class, Saved::class,
            Enabled::class, Disabled::class
        ], [$this, 'restart']);
    }

    public function restart()
    {
        /** @var Container $container */
        $container = resolve(Container::class);
        /** @var RestartCommand $command */
        $command = resolve(RestartCommand::class);

        $command->setLaravel($container);

        $command->run(
            new ArrayInput([]),
            new NullOutput
        );
    }
}
