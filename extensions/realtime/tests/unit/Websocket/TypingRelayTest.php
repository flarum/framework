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
use Flarum\Realtime\Websocket\TypingIdentity;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use stdClass;

/**
 * Covers how a typing event is split between the discussion channel (everyone who
 * can see the discussion) and the identified channel (only holders of
 * `user.viewLastSeenAt`). See Message::relayTyping().
 */
class TypingRelayTest extends TestCase
{
    private const DISCUSSION = 42;
    private const CHANNEL = 'private-typing=42';
    private const IDENTIFIED = 'private-typingIdentified=42';

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        Container::setInstance($this->container);

        // Typing also feeds the index dots; irrelevant here, but it resolves.
        $this->container->instance(IndexTypingPresence::class, $this->createStub(IndexTypingPresence::class));
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

    /**
     * @param string[] $socketIds
     */
    private function channel(string $name, ConnectionInterface $sender, array $socketIds = []): Channel
    {
        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasConnection', 'broadcastToEveryoneExcept', 'socketIds', 'getName'])
            ->getMock();

        $channel->method('hasConnection')->willReturnCallback(fn (ConnectionInterface $c) => $c === $sender);
        $channel->method('socketIds')->willReturn($socketIds);
        $channel->method('getName')->willReturn($name);

        return $channel;
    }

    /**
     * @param array<string, Channel> $channels
     */
    private function manager(array $channels, ?int $userId): Manager
    {
        $manager = $this->createStub(Manager::class);
        $manager->method('find')->willReturnCallback(fn (string $c) => $channels[$c] ?? null);
        $manager->method('userIdForConnection')->willReturn($userId);

        return $manager;
    }

    private function withIdentity(?string $displayName, bool $discloseOnline): void
    {
        $identity = $this->createStub(TypingIdentity::class);
        $identity->method('for')->willReturn(
            $displayName === null ? null : compact('displayName', 'discloseOnline')
        );

        $this->container->instance(TypingIdentity::class, $identity);
    }

    private function typingPayload(array $data = []): stdClass
    {
        return (object) [
            'event' => 'client-typing',
            'channel' => self::CHANNEL,
            'data' => (object) array_merge(['time' => 1700000000000], $data),
        ];
    }

    #[Test]
    public function names_the_typist_to_the_whole_discussion_when_they_disclose_their_online_status(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity('Bob', true);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with(
                $this->callback(fn (stdClass $p) => $p->data['displayName'] === 'Bob' && $p->data['discloseOnline'] === true),
                '1.1'
            );

        (new Message($this->typingPayload(), $sender, $this->manager([self::CHANNEL => $channel], 7)))->respond();
    }

    #[Test]
    public function withholds_the_name_from_the_discussion_channel_when_the_typist_is_hidden(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity('Bob', false);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data['displayName'] === null));

        (new Message($this->typingPayload(), $sender, $this->manager([self::CHANNEL => $channel], 7)))->respond();
    }

    #[Test]
    public function sends_the_name_to_the_identified_channel_when_the_typist_is_hidden(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $identified = $this->channel(self::IDENTIFIED, $sender, ['5.5']);
        $this->withIdentity('Bob', false);

        $identified->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(
                fn (stdClass $p) => $p->data['displayName'] === 'Bob'
                    && $p->data['discloseOnline'] === false
                    // Addressed to the identified channel, or pusher-js would route
                    // it to the wrong binding on the client.
                    && $p->channel === self::IDENTIFIED
            ));

        // The discussion channel still gets its anonymised copy.
        $channel->expects($this->once())->method('broadcastToEveryoneExcept');

        $manager = $this->manager([self::CHANNEL => $channel, self::IDENTIFIED => $identified], 7);

        (new Message($this->typingPayload(), $sender, $manager))->respond();
    }

    #[Test]
    public function excludes_identified_subscribers_from_the_anonymised_broadcast(): void
    {
        // A privileged viewer is subscribed to both channels; without this exclusion
        // they would see the same person as both "Bob" and "[Anonymous]".
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $identified = $this->channel(self::IDENTIFIED, $sender, ['5.5', '6.6']);
        $this->withIdentity('Bob', false);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with(
                $this->anything(),
                $this->callback(fn (array $except) => $except === ['1.1', '5.5', '6.6'])
            );

        $identified->expects($this->once())->method('broadcastToEveryoneExcept');

        $manager = $this->manager([self::CHANNEL => $channel, self::IDENTIFIED => $identified], 7);

        (new Message($this->typingPayload(), $sender, $manager))->respond();
    }

    #[Test]
    public function ignores_the_identity_claimed_in_the_payload(): void
    {
        // A modified client claiming to be a disclosing "Admin" must not be able to
        // put that name in front of the discussion, nor escape being anonymised.
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $identified = $this->channel(self::IDENTIFIED, $sender, ['5.5']);
        $this->withIdentity('Bob', false);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data['displayName'] === null));

        $identified->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data['displayName'] === 'Bob'));

        $manager = $this->manager([self::CHANNEL => $channel, self::IDENTIFIED => $identified], 7);
        $payload = $this->typingPayload(['displayName' => 'Admin', 'discloseOnline' => true]);

        (new Message($payload, $sender, $manager))->respond();
    }

    #[Test]
    public function falls_back_to_anonymous_when_the_sender_cannot_be_identified(): void
    {
        // No authenticated user channel for this socket — a guest, or a reconnecting
        // client whose channels aren't re-established yet. Fail closed.
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $identified = $this->channel(self::IDENTIFIED, $sender, ['5.5']);
        $this->withIdentity('Bob', false);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data['displayName'] === null));

        $identified->expects($this->never())->method('broadcastToEveryoneExcept');

        $manager = $this->manager([self::CHANNEL => $channel, self::IDENTIFIED => $identified], null);

        (new Message($this->typingPayload(), $sender, $manager))->respond();
    }

    #[Test]
    public function leaves_typing_on_other_channels_untouched(): void
    {
        // flarum/messages relays `client-typing` on its own dialog channel. That
        // isn't discussion typing, so it must pass through verbatim.
        $sender = $this->connection('1.1');
        $dialog = 'private-privateMessageTyping=3';
        $channel = $this->channel($dialog, $sender);
        $this->withIdentity('Bob', false);

        $payload = (object) [
            'event' => 'client-typing',
            'channel' => $dialog,
            'data' => (object) ['displayName' => 'Bob', 'discloseOnline' => true, 'time' => 1],
        ];

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->identicalTo($payload), '1.1');

        (new Message($payload, $sender, $this->manager([$dialog => $channel], 7)))->respond();
    }
}
