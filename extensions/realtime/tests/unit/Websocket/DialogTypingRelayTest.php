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
 * Typing in a private conversation (flarum/messages) is relayed on the same
 * terms as in a discussion: the typist is whoever the socket authenticated as,
 * and a member hiding their online status is named only on the channel that
 * holders of `user.viewLastSeenAt` may join. See Message::relayDialogTyping().
 */
class DialogTypingRelayTest extends TestCase
{
    private const CHANNEL = 'private-privateMessageTyping=9';
    private const IDENTIFIED = 'private-privateMessageTypingIdentified=9';

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        Container::setInstance($this->container);

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

    private function channel(string $name, ConnectionInterface $sender): Channel
    {
        $channel = $this->getMockBuilder(Channel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasConnection', 'broadcastToEveryoneExcept', 'socketIds', 'getName'])
            ->getMock();

        $channel->method('hasConnection')->willReturnCallback(fn (ConnectionInterface $c) => $c === $sender);
        $channel->method('socketIds')->willReturn([]);
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
            'data' => (object) $data,
        ];
    }

    #[Test]
    public function names_a_typist_who_discloses_their_online_status_to_the_conversation(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity('Bob', true);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data === ['userId' => 7] && $p->channel === self::CHANNEL), '1.1');

        (new Message($this->typingPayload(), $sender, $this->manager([self::CHANNEL => $channel], 7)))->respond();
    }

    #[Test]
    public function says_nothing_to_the_conversation_about_a_hidden_typist(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity('Bob', false);

        // In a two-member conversation, even "someone is typing" would name them.
        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        (new Message($this->typingPayload(), $sender, $this->manager([self::CHANNEL => $channel], 7)))->respond();
    }

    #[Test]
    public function names_a_hidden_typist_only_to_those_who_may_see_through_it(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $identified = $this->channel(self::IDENTIFIED, $sender);
        $this->withIdentity('Bob', false);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');
        $identified->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data === ['userId' => 7] && $p->channel === self::IDENTIFIED), '1.1');

        (new Message($this->typingPayload(), $sender, $this->manager([self::CHANNEL => $channel, self::IDENTIFIED => $identified], 7)))->respond();
    }

    #[Test]
    public function ignores_whoever_the_payload_claims_to_be(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity('Bob', true);

        $channel->expects($this->once())
            ->method('broadcastToEveryoneExcept')
            ->with($this->callback(fn (stdClass $p) => $p->data === ['userId' => 7]));

        (new Message($this->typingPayload(['userId' => 999, 'displayName' => 'Admin']), $sender, $this->manager([self::CHANNEL => $channel], 7)))->respond();
    }

    #[Test]
    public function says_nothing_for_a_sender_it_cannot_identify_rather_than_relaying_their_payload(): void
    {
        $sender = $this->connection('1.1');
        $channel = $this->channel(self::CHANNEL, $sender);
        $this->withIdentity(null, true);

        $channel->expects($this->never())->method('broadcastToEveryoneExcept');

        (new Message($this->typingPayload(['userId' => 999]), $sender, $this->manager([self::CHANNEL => $channel], null)))->respond();
    }
}
