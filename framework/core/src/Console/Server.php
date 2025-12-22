<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\SiteInterface;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

readonly class Server
{
    public function __construct(
        private SiteInterface $site
    ) {
    }

    public function listen(): never
    {
        $app = $this->site->bootApp();

        $console = new Application('Flarum', \Flarum\Foundation\Application::VERSION);

        foreach ($app->getConsoleCommands() as $command) {
            $console->add($command);
        }

        $this->handleEvents($console, $app->getContainer());

        exit($console->run());
    }

    private function handleEvents(Application $console, Container $container): void
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) use ($container) {
            $events = $container->make(Dispatcher::class);

            $events->dispatch(
                new CommandStarting($event->getCommand()->getName(), $event->getInput(), $event->getOutput())
            );
        });

        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) use ($container) {
            $events = $container->make(Dispatcher::class);

            $events->dispatch(
                new CommandFinished($event->getCommand()->getName(), $event->getInput(), $event->getOutput(), $event->getExitCode())
            );
        });

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) use ($container) {
            /** @var Registry $registry */
            $registry = $container->make(Registry::class);
            $error = $registry->handle($event->getError());

            /** @var Reporter[] $reporters */
            $reporters = $container->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });

        $console->setDispatcher($dispatcher);
    }
}
