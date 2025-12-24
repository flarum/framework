<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Connection;

use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\Concerns\Promises;
use Flarum\Realtime\Websocket\Exception\AppKeyNotAllowed;
use Flarum\Realtime\Websocket\Exception\OriginNotAllowed;
use Flarum\Realtime\Websocket\Exception\WebsocketException;
use Flarum\Realtime\Websocket\Message\Factory;
use Flarum\Realtime\Websocket\Server\QueryParams;
use Flarum\Realtime\Websocket\Settings;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class Websocket implements MessageComponentInterface
{
    use Promises;

    public function __construct(private Manager $manager)
    {
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        /** @phpstan-ignore-next-line */
        $conn->socketId = $conn->socketId ?? null;

        if (! $this->manager->allowsNewConnection()) {
            $conn->close();

            return;
        }

        $this
            ->verifyOrigin($conn)
            ->verifyAppKey($conn)
            ->generateSocketId($conn)
            ->establishConnection($conn);

        // Let's create a fulfilled promise
        $this->createFulfilledPromise(0);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->manager->unsubscribeFromAllChannels($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        if ($e instanceof WebsocketException) {
            $conn->send(json_encode($e->getPayload()));
        }
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg): void
    {
        Factory::forMessage(
            $msg,
            $conn,
            $this->manager
        )->respond();
    }

    private function verifyOrigin(ConnectionInterface $conn): Websocket
    {
        /** @phpstan-ignore-next-line */
        $header = (string) ($conn->httpRequest->getHeader('Origin')[0] ?? null);

        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (! in_array($origin, $this->manager->getUrls())) {
            throw new OriginNotAllowed;
        }

        return $this;
    }

    protected function generateSocketId(ConnectionInterface $conn): Websocket
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));

        /** @phpstan-ignore-next-line */
        $conn->socketId = $socketId;

        return $this;
    }

    protected function establishConnection(ConnectionInterface $conn): Websocket
    {
        $conn->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                /** @phpstan-ignore-next-line */
                'socket_id' => $conn->socketId,
                'activity_timeout' => 30,
            ]),
        ]));

        return $this;
    }

    protected function verifyAppKey(ConnectionInterface $conn): Websocket
    {
        /** @phpstan-ignore-next-line */
        $query = QueryParams::create($conn->httpRequest);

        $appKey = $query->get('appKey');

        /** @var Settings $settings */
        $settings = resolve(Settings::class);

        if ($appKey !== $settings->appKey) {
            throw new AppKeyNotAllowed($appKey);
        }

        return $this;
    }
}
