<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Foundation\Config;
use Flarum\Realtime\Websocket\Channel\Channel;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\Exception\InvalidSignature;
use Flarum\Realtime\Websocket\Settings;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use stdClass;

/**
 * Locks in the channel-registry lifecycle against unbounded growth from
 * unauthenticated input. The realtime server is a single long-lived process;
 * a channel object that is created but never removed is a permanent allocation.
 *
 * Three ways an unauthenticated client could make the registry grow without
 * bound, all confirmed live against the server before these tests existed:
 *
 *  - subscribe to many unique public names and never unsubscribe;
 *  - subscribe then immediately unsubscribe from each unique name, leaving the
 *    now-empty channel behind;
 *  - attempt private subscriptions with invalid signatures, each of which
 *    created the channel object before the signature was checked.
 *
 * The invariant: a channel exists only while at least one connection is
 * subscribed to it, and the number a single connection can hold is bounded.
 */
class ChannelLifecycleTest extends TestCase
{
    private const SECRET = 'test-app-secret';
    private const MAX_PER_CONNECTION = 100;

    protected function setUp(): void
    {
        parent::setUp();

        // Channel resolves Manager and Settings from the container in its
        // constructor; provide both.
        $container = new Container();

        $settings = $this->createStub(Settings::class);
        $settings->method('__get')->willReturnCallback(fn (string $name) => match ($name) {
            'appSecret' => self::SECRET,
            'maxConnections' => 1000,
            'maxChannelsPerConnection' => self::MAX_PER_CONNECTION,
            default => null,
        });

        $container->instance(Settings::class, $settings);
        // Channel's constructor resolves Manager; the real one is fine here.
        $container->instance(Manager::class, $this->manager($settings));

        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    private function manager(?Settings $settings = null): Manager
    {
        $settings ??= Container::getInstance()->get(Settings::class);

        $config = $this->createStub(Config::class);
        $config->method('url')->willReturn(new Uri('https://flarum.local'));

        return new Manager($settings, $config);
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
     * The channels the manager currently retains, read through its own public
     * accessor (a resolved promise).
     *
     * @return array<string, Channel>
     */
    private function channels(Manager $manager): array
    {
        $channels = [];
        $manager->getChannels()->then(function ($c) use (&$channels) {
            $channels = $c;
        });

        return $channels;
    }

    private function validAuth(string $socketId, string $channel): string
    {
        return 'test-app-key:'.hash_hmac('sha256', "$socketId:$channel", self::SECRET);
    }

    #[Test]
    public function unsubscribing_from_a_channel_removes_it_when_it_has_no_connections_left(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        $manager->subscribeToChannel($connection, 'public-a', new stdClass());
        $this->assertArrayHasKey('public-a', $this->channels($manager));

        $manager->unsubscribeFromChannel($connection, 'public-a', new stdClass());

        $this->assertArrayNotHasKey(
            'public-a',
            $this->channels($manager),
            'An empty channel must not linger in the registry after its last connection unsubscribes.'
        );
    }

    #[Test]
    public function unsubscribing_keeps_a_channel_that_still_has_other_connections(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $first = $this->connection('1.1');
        $second = $this->connection('2.2');

        $manager->subscribeToChannel($first, 'public-shared', new stdClass());
        $manager->subscribeToChannel($second, 'public-shared', new stdClass());

        $manager->unsubscribeFromChannel($first, 'public-shared', new stdClass());

        $this->assertArrayHasKey(
            'public-shared',
            $this->channels($manager),
            'A channel other connections are still subscribed to must survive one unsubscribe.'
        );
    }

    #[Test]
    public function subscribe_then_unsubscribe_churn_does_not_accumulate_channels(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        for ($i = 0; $i < 500; $i++) {
            $manager->subscribeToChannel($connection, "public-churn-$i", new stdClass());
            $manager->unsubscribeFromChannel($connection, "public-churn-$i", new stdClass());
        }

        $this->assertCount(
            0,
            $this->channels($manager),
            'Subscribe/unsubscribe churn must leave no channels behind.'
        );
    }

    #[Test]
    public function a_rejected_private_subscription_leaves_no_channel_behind(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        try {
            $manager->subscribeToChannel($connection, 'private-secret', (object) ['auth' => 'bad:signature']);
            $this->fail('An invalid signature should have thrown InvalidSignature.');
        } catch (InvalidSignature) {
            // expected
        }

        $this->assertArrayNotHasKey(
            'private-secret',
            $this->channels($manager),
            'A private channel created during a subscription that was then rejected must be removed.'
        );
    }

    #[Test]
    public function many_rejected_private_subscriptions_do_not_accumulate_channels(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        for ($i = 0; $i < 500; $i++) {
            try {
                $manager->subscribeToChannel($connection, "private-bad-$i", (object) ['auth' => 'bad:signature']);
            } catch (InvalidSignature) {
                // expected
            }
        }

        $this->assertCount(
            0,
            $this->channels($manager),
            'Repeated invalid private subscriptions must not grow the registry.'
        );
    }

    #[Test]
    public function a_connection_cannot_hold_more_than_the_configured_number_of_channels(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        for ($i = 0; $i < self::MAX_PER_CONNECTION + 50; $i++) {
            $manager->subscribeToChannel($connection, "public-cap-$i", new stdClass());
        }

        $this->assertCount(
            self::MAX_PER_CONNECTION,
            $this->channels($manager),
            'A single connection must not be able to allocate channels past the configured cap.'
        );
    }

    #[Test]
    public function the_cap_is_per_connection_not_global(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $first = $this->connection('1.1');
        $second = $this->connection('2.2');

        for ($i = 0; $i < self::MAX_PER_CONNECTION; $i++) {
            $manager->subscribeToChannel($first, "public-first-$i", new stdClass());
        }

        // The second connection starts from zero and can still subscribe.
        $subscribed = false;
        $manager->subscribeToChannel($second, 'public-second-0', new stdClass())
            ->then(function ($result) use (&$subscribed) {
                $subscribed = $result;
            });

        $this->assertTrue($subscribed, 'A fresh connection must not be blocked by another connection filling its own quota.');
        $this->assertArrayHasKey('public-second-0', $this->channels($manager));
    }

    #[Test]
    public function releasing_a_channel_frees_a_slot_against_the_cap(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        for ($i = 0; $i < self::MAX_PER_CONNECTION; $i++) {
            $manager->subscribeToChannel($connection, "public-full-$i", new stdClass());
        }

        // At the cap; a further distinct channel is refused.
        $manager->subscribeToChannel($connection, 'public-overflow', new stdClass());
        $this->assertArrayNotHasKey('public-overflow', $this->channels($manager));

        // Drop one, which should free a slot.
        $manager->unsubscribeFromChannel($connection, 'public-full-0', new stdClass());

        $accepted = false;
        $manager->subscribeToChannel($connection, 'public-after-release', new stdClass())
            ->then(function ($result) use (&$accepted) {
                $accepted = $result;
            });

        $this->assertTrue($accepted, 'Unsubscribing should free a slot for a new channel.');
        $this->assertArrayHasKey('public-after-release', $this->channels($manager));
    }

    #[Test]
    public function a_valid_public_subscription_still_succeeds(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        $result = null;
        $manager->subscribeToChannel($connection, 'public-ok', new stdClass())
            ->then(function ($r) use (&$result) {
                $result = $r;
            });

        $this->assertTrue($result);
        $this->assertArrayHasKey('public-ok', $this->channels($manager));
    }

    #[Test]
    public function a_valid_private_subscription_still_succeeds(): void
    {
        $manager = Container::getInstance()->get(Manager::class);
        $connection = $this->connection('1.1');

        $result = null;
        $manager->subscribeToChannel(
            $connection,
            'private-user=1',
            (object) ['auth' => $this->validAuth('1.1', 'private-user=1')]
        )->then(function ($r) use (&$result) {
            $result = $r;
        });

        $this->assertTrue($result);
        $this->assertArrayHasKey('private-user=1', $this->channels($manager));
    }
}
