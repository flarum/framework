<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Realtime\Websocket\Api\PresenceChannelAuthorizer;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Realtime\Websocket\TypingIdentity;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The websocket server is a single long-lived process, and these services hold
 * state for its lifetime — connected channels, the socket-to-user index, typing
 * presence, cached identities. Every one of them is silently useless if it isn't
 * bound as a singleton: the container hands out a fresh, empty instance per
 * resolution, and the state (or cache) never accumulates.
 *
 * That failure is invisible in unit tests, which inject their own shared stub, so
 * it is pinned here against the real container instead.
 */
class WebsocketBindingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function statefulServices(): array
    {
        return [
            'channel manager' => [Manager::class],
            'index typing presence' => [IndexTypingPresence::class],
            'presence channel authorizer' => [PresenceChannelAuthorizer::class],
            'typing identity cache' => [TypingIdentity::class],
            'realtime registry' => [RealtimeRegistry::class],
        ];
    }

    #[Test]
    #[DataProvider('statefulServices')]
    public function stateful_websocket_services_are_shared(string $class): void
    {
        $container = $this->app()->getContainer();

        $this->assertSame(
            $container->make($class),
            $container->make($class),
            "$class must be bound as a singleton, or its state is discarded between resolutions."
        );
    }
}
