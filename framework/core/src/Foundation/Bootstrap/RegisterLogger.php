<?php

namespace Flarum\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class RegisterLogger implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        /**
         * @var \Flarum\Foundation\Paths $paths
         * @var \Flarum\Foundation\Config $config
         */
        $paths = $app['flarum.paths'];
        $config = $app['flarum.config'];

        $logPath = $paths->storage.'/logs/flarum.log';
        $logLevel = $config->inDebugMode() ? Level::Debug : Level::Info;
        $handler = new RotatingFileHandler($logPath, 0, $logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $app->instance('log', new Logger('flarum', [$handler]));
        $app->alias('log', LoggerInterface::class);
    }
}
