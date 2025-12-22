<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Channel;

use Ratchet\ConnectionInterface;
use stdClass;

class PresenceChannel extends Channel
{
    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $user = json_decode($payload->channel_data);

        $this->manager
            ->userJoinedPresenceChannel($connection, $user, $this->getName(), $payload)
            ->then(function () use ($connection) {
                $this->manager->getChannelMembers($this->getName())
                    ->then(function ($users) use ($connection) {
                        $hash = [];

                        foreach ($users as $socketId => $user) {
                            $hash[$user->user_id] = $user->user_info ?? [];
                        }

                        $connection->send(json_encode([
                            'event' => 'pusher_internal:subscription_succeeded',
                            'channel' => $this->getName(),
                            'data' => json_encode([
                                'presence' => [
                                    /** @phpstan-ignore-next-line */
                                    'ids' => collect($users)->map(function ($user) {
                                        return (string) $user->user_id;
                                    })->values(),
                                    'hash' => $hash,
                                    'count' => count($users),
                                ],
                            ]),
                        ]));
                    });
            })
            ->then(function () use ($connection, $user, $payload) {
                $this->manager
                    ->getMemberSockets($user->user_id, $this->getName())
                    ->then(function ($sockets) use ($payload, $connection) {
                        if (count($sockets) === 1) {
                            $memberAddedPayload = [
                                'event' => 'pusher_internal:member_added',
                                'channel' => $this->getName(),
                                'data' => $payload->channel_data,
                            ];

                            $this->broadcastToEveryoneExcept(
                                (object) $memberAddedPayload,
                                /** @phpstan-ignore-next-line */
                                $connection->socketId
                            );
                        }
                    });
            });

        return true;
    }

    public function unsubscribe(ConnectionInterface $connection): bool
    {
        $truth = parent::unsubscribe($connection);

        $this->manager
            ->getChannelMember($connection, $this->getName())
            ->then(function ($user) {
                return @json_decode($user);
            })
            ->then(function ($user) use ($connection) {
                if (! $user) {
                    return;
                }

                $this->manager
                    ->userLeftPresenceChannel($connection, $user, $this->getName())
                    ->then(function () use ($connection, $user) {
                        $this->manager
                            ->getMemberSockets($user->user_id, $this->getName())
                            ->then(function ($sockets) use ($connection, $user) {
                                if (count($sockets) === 0) {
                                    $memberRemovedPayload = [
                                        'event' => 'pusher_internal:member_removed',
                                        'channel' => $this->getName(),
                                        'data' => json_encode([
                                            'user_id' => $user->user_id,
                                        ]),
                                    ];

                                    $this->broadcastToEveryoneExcept(
                                        (object) $memberRemovedPayload,
                                        /** @phpstan-ignore-next-line */
                                        $connection->socketId
                                    );
                                }
                            });
                    });
            });

        return $truth;
    }
}
