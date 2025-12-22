<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Logger;

use Ratchet\ConnectionInterface;

class ConnectionLogger extends Logger implements ConnectionInterface
{
    /**
     * The connection to watch.
     *
     * @var \Ratchet\ConnectionInterface
     */
    protected $connection;

    /**
     * Create a new instance and add a connection to watch.
     *
     * @param  \Ratchet\ConnectionInterface $app
     * @return self
     */
    public static function decorate(ConnectionInterface $app): self
    {
        $logger = resolve(self::class);

        return $logger->setConnection($app);
    }

    /**
     * Set a new connection to watch.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function send($data): self
    {
        /** @phpstan-ignore-next-line */
        $socketId = $this->connection->socketId ?? null;

        $this->info("Connection id {$socketId} sending message {$data}");

        $this->connection->send($data);

        return $this;
    }

    /**
     * Close the connection.
     *
     * @return void
     */
    public function close()
    {
        /** @phpstan-ignore-next-line */
        $this->warn("Connection id {$this->connection->socketId} closing.");

        $this->connection->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __set(string $name, mixed $value): void
    {
        $this->connection->$name = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __get(string $name): mixed
    {
        return $this->connection->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset(string $name): bool
    {
        return isset($this->connection->$name);
    }

    /**
     * {@inheritdoc}
     */
    public function __unset(string $name): void
    {
        unset($this->connection->$name);
    }
}
