<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Logger;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class HttpLogger extends Logger implements MessageComponentInterface
{
    /**
     * The HTTP app instance to watch.
     *
     * @var \Ratchet\Http\HttpServerInterface
     */
    protected $app;

    /**
     * Create a new instance and add the app to watch.
     *
     * @param  \Ratchet\MessageComponentInterface  $app
     * @return self
     */
    public static function decorate(MessageComponentInterface $app): self
    {
        $logger = app(self::class);

        return $logger->setApp($app);
    }

    /**
     * Set a new app to watch.
     *
     * @param  \Ratchet\MessageComponentInterface  $app
     * @return $this
     */
    public function setApp(MessageComponentInterface $app)
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
        $this->app->onOpen($connection);
    }

    /**
     * Handle the HTTP message request.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  mixed  $message
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, $message)
    {
        $this->app->onMessage($connection, $message);
    }

    /**
     * Handle the HTTP close request.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->app->onClose($connection);
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

        $message = "Exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError($connection, $exception);
    }
}
