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
use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Server
{
    public function __construct(
        private readonly SiteInterface $site
    ) {
    }

    public function listen(): never
    {
        $app = $this->site->bootApp();

        $console = new Application('Flarum', \Flarum\Foundation\Application::VERSION);

        foreach ($app->getConsoleCommands() as $command) {
            $console->add($command);
        }

        $this->handleErrors($console);

        exit($console->run());
    }

    private function handleErrors(Application $console): void
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
            $container = Container::getInstance();

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
