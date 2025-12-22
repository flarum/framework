<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Logger;

use Exception;
use Flarum\Realtime\Websocket\Server\QueryParams;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebsocketLogger extends Logger implements MessageComponentInterface
{
    /**
     * The HTTP app instance to watch.
     *
     * @var \Ratchet\Http\HttpServerInterface
     */
    protected $app;

    public static function decorate(MessageComponentInterface $app): self
    {
        $logger = clone resolve(self::class);

        return $logger->setApp($app);
    }

    public function setApp(MessageComponentInterface $app): self
    {
        /** @phpstan-ignore-next-line */
        $this->app = $app;

        return $this;
    }

    /**
     * Handle the HTTP open request.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection)
    {
        /** @phpstan-ignore-next-line */
        $appKey = QueryParams::create($connection->httpRequest)->get('appKey');

        $this->warn("New connection opened for app key {$appKey}.");

        $this->app->onOpen(ConnectionLogger::decorate($connection));
    }

    /**
     * Handle the HTTP message request.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \Ratchet\RFC6455\Messaging\MessageInterface  $message
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        /** @phpstan-ignore-next-line */
        $this->info("Connection id {$connection->socketId} received message: {$message->getPayload()}.");

        $this->app->onMessage(ConnectionLogger::decorate($connection), $message);
    }

    /**
     * Handle the HTTP close request.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection)
    {
        /** @phpstan-ignore-next-line */
        $socketId = $connection->socketId ?? null;

        $this->warn("Connection id {$socketId} closed.");

        $this->app->onClose(ConnectionLogger::decorate($connection));
    }

    /**
     * Handle HTTP errors.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  Exception  $exception
     * @return void
     */
    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $message = "Exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`.";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError(ConnectionLogger::decorate($connection), $exception);
    }
}
