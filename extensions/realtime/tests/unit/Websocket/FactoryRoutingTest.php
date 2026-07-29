<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\Channel\Channel;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Realtime\Websocket\Message\Factory;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

/**
 * Drives the real websocket entry point — Factory::forMessage(...)->respond() —
 * with raw client payloads, reproducing the reported injection scenarios end to
 * end (frame routing + authorization), not just the Message logic in isolation
 * (see MessageTest). This guards against a regression that re-routes non-pusher:
 * frames around the authorization gate.
 */
class FactoryRoutingTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    private function connection(string $socketId): ConnectionInterface
    {
        $connection = new class implements ConnectionInterface {
            public ?string $socketId = null;

            public function send($data): void
            {
            }

            public function close(): void
            {
            }
        };

        $connection->socketId = $socketId;

        return $connection;
    }

    private function frame(array $payload): MessageInterface
    {
        $message = $this->createStub(MessageInterface::class);
        $message->method('getPayload')->willReturn(json_encode($payload));

        return $message;
    }

    /**
     * @return array{0: Manager, 1: Channel}
     */
    private function managerWithChannel(string $name, bool $senderSubscribed, ConnectionInterface $sender): array
    {
        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasConnection', 'broadcastToEveryoneExcept'])
            ->getMock();

        $channel->method('hasConnection')
            ->willReturnCallback(fn (ConnectionInterface $c) => $senderSubscribed && $c === $sender);

        $manager = $this->createStub(Manager::class);
        $manager->method('find')
            ->willReturnCallback(fn (string $c) => $c === $name ? $channel : null);

        return [$manager, $channel];
    }

    #[Test]
    public function forged_notification_into_private_channel_is_dropped_via_the_entry_point(): void
    {
        // The exact attack from the report: an unsubscribed connection routes a
        // non-pusher: `notification` frame at someone else's private channel.
        $attacker = $this->connection('9.9');
        [$manager, $channel] = $this->managerWithChannel('private-user=1', false, $attacker);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $frame = $this->frame([
            'event' => 'notification',
            'channel' => 'private-user=1',
            'data' => json_encode(['data' => ['type' => 'notifications']]),
        ]);

        Factory::forMessage($frame, $attacker, $manager)->respond();
    }

    #[Test]
    public function legitimate_client_event_routes_through_and_broadcasts(): void
    {
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('private-user=1', true, $sender);

        $channel->expects($this->once())->method('broadcastToEveryoneExcept');

        $frame = $this->frame([
            'event' => 'client-something',
            'channel' => 'private-user=1',
            'data' => [],
        ]);

        Factory::forMessage($frame, $sender, $manager)->respond();
    }

    #[Test]
    public function forged_client_typing_does_not_reach_index_presence_via_the_entry_point(): void
    {
        $attacker = $this->connection('9.9');
        [$manager, $channel] = $this->managerWithChannel('private-typing=42', false, $attacker);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $presence = $this->createMock(IndexTypingPresence::class);
        $presence->expects($this->never())->method('touch');
        $this->container->instance(IndexTypingPresence::class, $presence);

        $frame = $this->frame([
            'event' => 'client-typing',
            'channel' => 'private-typing=42',
            'data' => [],
        ]);

        Factory::forMessage($frame, $attacker, $manager)->respond();
    }
}
