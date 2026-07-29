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
use Flarum\Realtime\Websocket\Message\Message;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use stdClass;

class MessageTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // Message::relayIndexTyping() uses the resolve() helper, which reads from
        // the global container. Give it a clean one per test.
        $this->container = new Container();
        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    /**
     * A connection keyed by socket id, matching how Channel stores connections.
     * Uses a declared-property stub rather than a mock so $socketId is a real
     * property (the websocket server stamps it dynamically in production).
     */
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

    private function payload(string $event, mixed $channel, array $extra = []): stdClass
    {
        return (object) array_merge([
            'event' => $event,
            'channel' => $channel,
            'data' => new stdClass(),
        ], $extra);
    }

    /**
     * A Manager whose find() returns a channel that reports whether the sender is
     * subscribed, and records whether it was broadcast to.
     *
     * @return array{0: Manager, 1: Channel}
     */
    private function managerWithChannel(string $name, bool $senderSubscribed, ConnectionInterface $sender): array
    {
        // Channel's constructor resolves Manager/Settings from the container, which
        // we don't want in a unit test — bypass it and stub only what we exercise.
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
    public function rebroadcasts_client_event_when_sender_is_subscribed_to_private_channel(): void
    {
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('private-user=1', true, $sender);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->anything(), '1.1');

        $payload = $this->payload('client-something', 'private-user=1');

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function drops_event_when_sender_is_not_subscribed_to_the_channel(): void
    {
        // The core CVE: an attacker that never subscribed forges an event into a
        // private channel an online victim is subscribed to.
        $attacker = $this->connection('9.9');
        [$manager, $channel] = $this->managerWithChannel('private-user=1', false, $attacker);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $payload = $this->payload('client-something', 'private-user=1');

        (new Message($payload, $attacker, $manager))->respond();
    }

    #[Test]
    public function drops_non_client_event_even_when_sender_is_subscribed(): void
    {
        // Pusher only permits `client-` prefixed events from clients. A forged
        // `notification` must never be rebroadcast, even by a subscribed connection.
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('private-user=1', true, $sender);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $payload = $this->payload('notification', 'private-user=1');

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function drops_client_event_on_a_public_channel(): void
    {
        // Client events are forbidden on public channels.
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('public-foo', true, $sender);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $payload = $this->payload('client-something', 'public-foo');

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function rebroadcasts_client_event_on_a_presence_channel_when_subscribed(): void
    {
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('presence-foo', true, $sender);

        $channel->expects($this->once())->method('broadcastToEveryoneExcept');

        $payload = $this->payload('client-something', 'presence-foo');

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function drops_event_with_non_string_channel_without_error(): void
    {
        $sender = $this->connection('1.1');
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('find');

        $payload = $this->payload('client-something', null);

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function drops_event_when_channel_does_not_exist(): void
    {
        $sender = $this->connection('1.1');
        $manager = $this->createStub(Manager::class);
        $manager->method('find')->willReturn(null);

        // Reaching find() with a well-formed client event on a private channel is
        // fine; the guard is that a missing channel simply yields no broadcast.
        $payload = $this->payload('client-something', 'private-user=1');

        (new Message($payload, $sender, $manager))->respond();

        $this->assertTrue(true); // no exception thrown
    }

    #[Test]
    public function relays_client_typing_to_index_presence_when_authorized(): void
    {
        $sender = $this->connection('1.1');
        [$manager, $channel] = $this->managerWithChannel('private-typing=42', true, $sender);

        $channel->expects($this->once())->method('broadcastToEveryoneExcept');

        $presence = $this->createMock(IndexTypingPresence::class);
        $presence->expects($this->once())->method('touch')->with(42);
        $this->container->instance(IndexTypingPresence::class, $presence);

        $payload = $this->payload('client-typing', 'private-typing=42');

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function does_not_relay_index_typing_for_unauthorized_client_typing(): void
    {
        $attacker = $this->connection('9.9');
        [$manager, $channel] = $this->managerWithChannel('private-typing=42', false, $attacker);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        $presence = $this->createMock(IndexTypingPresence::class);
        $presence->expects($this->never())->method('touch');
        $this->container->instance(IndexTypingPresence::class, $presence);

        $payload = $this->payload('client-typing', 'private-typing=42');

        (new Message($payload, $attacker, $manager))->respond();
    }
}
