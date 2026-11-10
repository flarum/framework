<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\Channel\PresenceChannel;
use Flarum\Realtime\Websocket\Channel\PrivateChannel;
use Flarum\Realtime\Websocket\Exception\InvalidSignature;
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

/**
 * Locks in the authorization boundary that the realtime confidentiality model
 * depends on: a connection may only subscribe to a private or presence channel
 * if it presents a valid HMAC signature (issued server-side by the
 * permission-checked /api/websocket/auth endpoint).
 *
 * This is the sibling of the client-event injection check (see MessageTest). If
 * it ever regresses, an attacker holding only the public app key could subscribe
 * to another user's private channel and *read* their realtime stream — a far
 * more severe (confidentiality) break than the injection bug. These tests fail
 * loudly if that gate is removed or weakened.
 */
class ChannelSubscriptionAuthTest extends TestCase
{
    private const SECRET = 'test-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        // Channel resolves Manager and Settings from the container in its
        // constructor; provide both. Settings is stubbed so reading `appSecret`
        // returns a known value without going through the full validation path.
        $container = new Container();

        $settings = $this->createStub(Settings::class);
        $settings->method('__get')->willReturnCallback(
            fn (string $name) => $name === 'appSecret' ? self::SECRET : null
        );

        $container->instance(Settings::class, $settings);
        $container->instance(Manager::class, $this->createStub(Manager::class));

        Container::setInstance($container);
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

    private function validAuth(string $socketId, string $channel, ?string $channelData = null): string
    {
        $signature = "$socketId:$channel";

        if ($channelData !== null) {
            $signature .= ":$channelData";
        }

        return 'test-app-key:'.hash_hmac('sha256', $signature, self::SECRET);
    }

    #[Test]
    public function private_channel_rejects_subscription_without_a_signature(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PrivateChannel('private-user=1');

        $this->expectException(InvalidSignature::class);

        // No `auth` at all — the forged-subscribe attempt.
        $channel->subscribe($connection, (object) ['channel' => 'private-user=1']);
    }

    #[Test]
    public function private_channel_rejects_subscription_with_a_wrong_signature(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PrivateChannel('private-user=1');

        $this->expectException(InvalidSignature::class);

        $channel->subscribe($connection, (object) [
            'channel' => 'private-user=1',
            'auth' => 'test-app-key:'.str_repeat('0', 64),
        ]);
    }

    #[Test]
    public function private_channel_rejects_a_signature_minted_for_a_different_channel(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PrivateChannel('private-user=1');

        $this->expectException(InvalidSignature::class);

        // A valid signature, but for someone else's channel — must not transfer.
        $channel->subscribe($connection, (object) [
            'channel' => 'private-user=1',
            'auth' => $this->validAuth('1.1', 'private-user=2'),
        ]);
    }

    #[Test]
    public function private_channel_accepts_a_correctly_signed_subscription(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PrivateChannel('private-user=1');

        $result = $channel->subscribe($connection, (object) [
            'channel' => 'private-user=1',
            'auth' => $this->validAuth('1.1', 'private-user=1'),
        ]);

        $this->assertTrue($result);
        $this->assertTrue($channel->hasConnection($connection));
    }

    #[Test]
    public function presence_channel_rejects_subscription_without_a_signature(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PresenceChannel('presence-foo');

        $this->expectException(InvalidSignature::class);

        $channel->subscribe($connection, (object) [
            'channel' => 'presence-foo',
            'channel_data' => json_encode(['user_id' => 1]),
        ]);
    }

    #[Test]
    public function presence_channel_rejects_subscription_with_a_wrong_signature(): void
    {
        $connection = $this->connection('1.1');
        $channel = new PresenceChannel('presence-foo');

        $this->expectException(InvalidSignature::class);

        $channel->subscribe($connection, (object) [
            'channel' => 'presence-foo',
            'channel_data' => json_encode(['user_id' => 1]),
            'auth' => 'test-app-key:'.str_repeat('0', 64),
        ]);
    }
}
