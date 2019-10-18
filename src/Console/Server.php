<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Console\Event\Configuring;
use Flarum\Foundation\Application;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\SiteInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Server
{
    private $site;

    public function __construct(SiteInterface $site)
    {
        $this->site = $site;
    }

    public function listen()
    {
        $app = $this->site->bootApp();

        $console = new ConsoleApplication('Flarum', Application::VERSION);

        foreach ($app->getConsoleCommands() as $command) {
            $console->add($command);
        }

        $this->extend($console);

        exit($console->run());
    }

    private function extend(ConsoleApplication $console)
    {
        $app = Application::getInstance();

        $this->handleErrors($app, $console);

        $events = $app->make(Dispatcher::class);

        $events->fire(new Configuring($app, $console));
    }

    private function handleErrors(Application $app, ConsoleApplication $console)
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) use ($app) {
            /** @var Registry $registry */
            $registry = $app->make(Registry::class);

            $error = $registry->handle($event->getError());

            /** @var Reporter[] $reporters */
            $reporters = $app->tagged(Reporter::class);

            if ($error->shouldBeReported()) {
                foreach ($reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }
        });

        $console->setDispatcher($dispatcher);
    }
}
