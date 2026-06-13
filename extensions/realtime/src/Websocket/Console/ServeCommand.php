<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Console;

use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Realtime\Websocket\Logger\ConnectionLogger;
use Flarum\Realtime\Websocket\Logger\HttpLogger;
use Flarum\Realtime\Websocket\Logger\WebsocketLogger;
use Flarum\Realtime\Websocket\Server\HttpServer;
use Flarum\Realtime\Websocket\Settings;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Socket\SocketServer;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class ServeCommand extends Command
{
    protected $signature = 'realtime:serve
        {--ignore-extension-toggles : Ignore killing the daemon when extensions are en- or disabled}
        {--debug : Allow debugging of all connections}';
    protected $description = 'Starts the realtime websocket server using the configured settings. (daemonize this)';

    public function handle(Settings $settings, Repository $cache): void
    {
        $this->loggers();

        $loop = Loop::get();

        $this->restartOnCachedSignal($loop, $cache);
        $this->restartOnExtensionChanges($loop);
        $this->sweepIndexTyping($loop);

        $this->getLaravel()->instance(LoopInterface::class, $loop);

        // Catch everything, including notices when we run the php serve command
        // with --debug.
        set_error_handler(
            [$this, 'errorHandler'],
            $this->option('debug') ? E_ALL : E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED
        );

        $socket = new SocketServer(uri: $listensOn = "{$settings->serverHost}:{$settings->serverPort}", loop: $loop);

        $routes = tap(new RouteCollection, function ($router) {
            $routes = include __DIR__.'/../../../resources/routes/websocket.php';

            $routes($router);
        });

        $app = new Router(
            new UrlMatcher($routes, new RequestContext)
        );

        $maxRequestSize = \getenv('WSS_MAX_REQUEST_SIZE_KB') ?: 256;

        $http = new HttpServer(
            $app,
            $maxRequestSize * 1024
        );

        if (HttpLogger::isEnabled()) {
            $http = HttpLogger::decorate($http);
        }

        $io = new IoServer($http, $socket, $loop);

        $this->info("Starting websocket server, which listens on $listensOn.");
        $this->info("App key expected is $settings->appKey");
        $this->info("App secret expected is $settings->appSecret");
        $this->info("Max connections $settings->maxConnections");

        $io->run();
    }

    protected function loggers(): void
    {
        $debug = $this->option('debug');

        $this->getLaravel()->singleton(HttpLogger::class, function () use ($debug) {
            return (new HttpLogger($this->output))
                ->enable($debug)
                ->verbose($this->output->isVerbose());
        });

        $this->getLaravel()->singleton(WebsocketLogger::class, function () use ($debug) {
            return (new WebsocketLogger($this->output))
                ->enable($debug)
                ->verbose($this->output->isVerbose());
        });

        $this->getLaravel()->bind(ConnectionLogger::class, function () use ($debug) {
            return (new ConnectionLogger($this->output))
                ->enable($debug)
                ->verbose($this->output->isVerbose());
        });
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Don't throw exceptions for deprecation notices, warnings, or notices in debug mode
        // Just log them to output instead
        if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE])) {
            if ($this->option('debug')) {
                $this->warn("[$errno] $errstr in $errfile:$errline");
            }

            return true;
        }

        // Throw exceptions for actual errors
        throw new \Exception("$errno, $errstr, $errfile:$errline");
    }

    /**
     * Expire stale index-typing presence and emit falling-edge "stopped typing"
     * signals, so list dots clear without each client running its own per-discussion
     * timer. Swept faster than the TTL (6s) to keep the clear-lag small.
     */
    protected function sweepIndexTyping(LoopInterface $loop): void
    {
        $presence = $this->getLaravel()->make(IndexTypingPresence::class);

        $loop->addPeriodicTimer(2, function () use ($presence) {
            $presence->sweep();
        });
    }

    protected function restartOnCachedSignal(LoopInterface $loop, Repository $cache): void
    {
        $loop->addPeriodicTimer(10, function (TimerInterface $timer) use ($loop, $cache) {
            if ($cache->has($key = HaltCommand::KEY)) {
                $this->warn('Halt signal received, killing to restart.');

                $cache->forget($key);

                $loop->stop();
            }
        });
    }

    protected function restartOnExtensionChanges(LoopInterface $loop): void
    {
        $enabled = null;

        $loop->addPeriodicTimer(10, function (TimerInterface $timer) use ($loop, &$enabled) {
            $app = $this->getLaravel();

            // If a Redis-backed settings cache is bound (e.g. fof/redis), use the settings
            // repository which will read from Redis. Otherwise fall back to a direct DB query,
            // because the default MemoryCacheSettingsRepository is a per-process in-memory cache
            // that never reflects external changes in a long-running process.
            if ($app->bound('cache.settings')) {
                $newState = $app->make(SettingsRepositoryInterface::class)->get('extensions_enabled');
            } else {
                /** @var ConnectionInterface $connection */
                $connection = $app->make(ConnectionInterface::class);
                $newState = $connection->table('settings')
                    ->where('key', 'extensions_enabled')
                    ->value('value');
            }

            if ($enabled === null || $newState === $enabled) {
                $enabled = $newState;

                return;
            }

            if ($this->option('ignore-extension-toggles')) {
                $enabled = $newState;

                $this->warn('One or more extensions have changed, but ignoring due to flag --ignore-extension-toggles.');

                return;
            }

            $this->warn('One or more extensions have changed, killing to restart.');

            $loop->stop();
        });
    }
}
