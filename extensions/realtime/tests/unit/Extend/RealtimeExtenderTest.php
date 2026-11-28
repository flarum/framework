<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Extend;

use Flarum\Realtime\Extend\Realtime as RealtimeExtender;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Realtime\Websocket\Api\ChannelRegistry;
use Flarum\Realtime\Websocket\Settings;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RealtimeExtenderTest extends TestCase
{
    private RealtimeRegistry $registry;
    private ChannelRegistry $channels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new RealtimeRegistry();
        $this->channels = new ChannelRegistry();
    }

    /**
     * Runs the extender against a fake container that immediately calls the
     * afterResolving callbacks for Settings, RealtimeRegistry and ChannelRegistry.
     */
    private function runExtender(RealtimeExtender $extender, array $instances = []): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('use');

        $container = $this->createMock(Container::class);

        $registry = $this->registry;
        $channels = $this->channels;

        $container
            ->method('afterResolving')
            ->willReturnCallback(function (string $abstract, callable $callback) use ($settings, $registry, $channels) {
                if ($abstract === Settings::class) {
                    $callback($settings);
                }

                if ($abstract === RealtimeRegistry::class) {
                    $callback($registry);
                }

                if ($abstract === ChannelRegistry::class) {
                    $callback($channels);
                }
            });

        $container
            ->method('make')
            ->willReturnCallback(fn (string $abstract) => $instances[$abstract]);

        $extender->extend($container);
    }

    #[Test]
    public function broadcast_model_event_registers_with_registry(): void
    {
        $getModel = fn ($e) => $e->post;
        $getActor = fn ($e) => $e->user;

        $extender = (new RealtimeExtender())
            ->broadcastModelEvent(
                ['EventA', 'EventB'],
                $getModel,
                $getActor,
                'testMutation'
            );

        $this->runExtender($extender);

        $events = $this->registry->getModelEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['EventA', 'EventB'], $events[0]['events']);
        $this->assertSame($getModel, $events[0]['getModel']);
        $this->assertSame($getActor, $events[0]['getActor']);
        $this->assertSame('testMutation', $events[0]['eventName']);
    }

    #[Test]
    public function broadcast_model_event_accepts_a_single_event_string(): void
    {
        $extender = (new RealtimeExtender())
            ->broadcastModelEvent('SingleEvent', fn ($e) => $e);

        $this->runExtender($extender);

        $this->assertSame(['SingleEvent'], $this->registry->getModelEvents()[0]['events']);
    }

    #[Test]
    public function broadcast_dialog_event_registers_with_registry(): void
    {
        $getMessage = fn ($e) => $e->message;

        $extender = (new RealtimeExtender())
            ->broadcastDialogEvent('DialogEvent', $getMessage);

        $this->runExtender($extender);

        $events = $this->registry->getDialogEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['DialogEvent'], $events[0]['events']);
        $this->assertSame($getMessage, $events[0]['getMessage']);
    }

    #[Test]
    public function broadcast_dialog_removal_registers_with_registry(): void
    {
        $getRemoval = fn ($e) => ['id' => 1, 'dialogId' => 2, 'dialogDeleted' => false, 'userIds' => [3]];

        $extender = (new RealtimeExtender())
            ->broadcastDialogRemoval('DialogRemoval', $getRemoval);

        $this->runExtender($extender);

        $removals = $this->registry->getDialogRemovals();

        $this->assertCount(1, $removals);
        $this->assertSame(['DialogRemoval'], $removals[0]['events']);
        $this->assertSame($getRemoval, $removals[0]['getRemoval']);
    }

    #[Test]
    public function broadcast_to_users_registers_with_registry(): void
    {
        $getPayloads = fn ($e) => [3 => ['dialogId' => '2']];

        $extender = (new RealtimeExtender())
            ->broadcastToUsers(['UserRead', 'UserReadAgain'], $getPayloads);

        $this->runExtender($extender);

        $broadcasts = $this->registry->getUserBroadcasts();

        $this->assertCount(1, $broadcasts);
        $this->assertSame(['UserRead', 'UserReadAgain'], $broadcasts[0]['events']);
        $this->assertSame($getPayloads, $broadcasts[0]['getPayloads']);
    }

    #[Test]
    public function broadcast_flag_event_registers_with_registry(): void
    {
        $getDiscussion = fn ($e) => $e->flag->post->discussion;

        $extender = (new RealtimeExtender())
            ->broadcastFlagEvent(['FlagCreated', 'FlagDeleting'], $getDiscussion, 'flagged');

        $this->runExtender($extender);

        $events = $this->registry->getFlagEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['FlagCreated', 'FlagDeleting'], $events[0]['events']);
        $this->assertSame($getDiscussion, $events[0]['getDiscussion']);
        $this->assertSame('flagged', $events[0]['eventName']);
    }

    #[Test]
    public function register_model_endpoint_registers_with_registry(): void
    {
        $extender = (new RealtimeExtender())
            ->registerModelEndpoint('Flarum\\Messages\\Dialog', 'dialogs')
            ->registerModelEndpoint('Flarum\\Messages\\DialogMessage', 'dialog-messages');

        $this->runExtender($extender);

        $endpoints = $this->registry->getModelEndpoints();

        $this->assertSame('dialogs', $endpoints['Flarum\\Messages\\Dialog']);
        $this->assertSame('dialog-messages', $endpoints['Flarum\\Messages\\DialogMessage']);
    }

    #[Test]
    public function private_channel_registers_with_the_channel_registry(): void
    {
        $extender = (new RealtimeExtender())
            ->privateChannel('acme-readers', fn (User $actor, int $id) => $id === 1);

        $this->runExtender($extender);

        $this->assertTrue($this->channels->authorizePrivate('acme-readers', new User, 1));
        $this->assertFalse($this->channels->authorizePrivate('acme-readers', new User, 2));
    }

    #[Test]
    public function presence_channel_registers_with_the_channel_registry(): void
    {
        $extender = (new RealtimeExtender())
            ->presenceChannel('acme-readers', fn (User $actor, ?int $id) => ['scopedTo' => $id]);

        $this->runExtender($extender);

        $this->assertSame(
            ['scopedTo' => 5],
            $this->channels->authorizePresence('acme-readers', new User, 5)
        );
    }

    #[Test]
    public function extender_can_be_chained(): void
    {
        $extender = (new RealtimeExtender())
            ->broadcastModelEvent('EventA', fn ($e) => $e, null, 'nameA')
            ->broadcastModelEvent('EventB', fn ($e) => $e, null, 'nameB')
            ->broadcastDialogEvent('DialogEvent', fn ($e) => $e->message)
            ->broadcastFlagEvent('FlagEvent', fn ($e) => $e->discussion, 'flagged')
            ->registerModelEndpoint('MyModel', 'my-models')
            ->privateChannel('acme-readers', fn () => true)
            ->presenceChannel('acme-readers', fn () => ['ok' => true]);

        $this->runExtender($extender);

        $this->assertCount(2, $this->registry->getModelEvents());
        $this->assertCount(1, $this->registry->getDialogEvents());
        $this->assertCount(1, $this->registry->getFlagEvents());
        $this->assertCount(1, $this->registry->getModelEndpoints());
        $this->assertTrue($this->channels->authorizePrivate('acme-readers', new User, 1));
        $this->assertSame(['ok' => true], $this->channels->authorizePresence('acme-readers', new User, 1));
    }

    /**
     * Within one extender, a second registration for a subject used to overwrite
     * the first in the extender's own array, so the registry never saw the clash
     * and the later — possibly more permissive — callback silently won.
     */
    #[Test]
    public function registering_a_private_subject_twice_on_one_extender_throws(): void
    {
        $extender = (new RealtimeExtender())->privateChannel('acme', fn () => false);

        $this->expectException(\InvalidArgumentException::class);

        $extender->privateChannel('acme', fn () => true);
    }

    #[Test]
    public function registering_a_presence_subject_twice_on_one_extender_throws(): void
    {
        $extender = (new RealtimeExtender())->presenceChannel('acme', fn () => false);

        $this->expectException(\InvalidArgumentException::class);

        $extender->presenceChannel('acme', fn () => ['ok' => true]);
    }

    #[Test]
    public function private_and_presence_channels_may_share_a_subject_on_one_extender(): void
    {
        $extender = (new RealtimeExtender())
            ->privateChannel('acme', fn () => true)
            ->presenceChannel('acme', fn () => ['ok' => true]);

        $this->runExtender($extender);

        $this->assertTrue($this->channels->authorizePrivate('acme', new User, 1));
    }

    /**
     * Across extenders the registry sees both registrations, and fails closed.
     */
    #[Test]
    public function the_same_subject_from_two_extenders_is_refused(): void
    {
        $this->runExtender((new RealtimeExtender())->privateChannel('acme', fn () => true));
        $this->runExtender((new RealtimeExtender())->privateChannel('acme', fn () => true));

        $this->assertFalse($this->channels->authorizePrivate('acme', new User, 1));
    }

    #[Test]
    public function a_forum_wide_channel_is_registered_without_an_id(): void
    {
        $extender = (new RealtimeExtender())
            ->privateChannel('acme-feed', fn () => true, scoped: false)
            ->presenceChannel('acme-room', fn () => ['ok' => true], scoped: false);

        $this->runExtender($extender);

        $this->assertTrue($this->channels->authorizePrivate('acme-feed', new User, null));
        $this->assertFalse($this->channels->authorizePrivate('acme-feed', new User, 1));
        $this->assertSame(['ok' => true], $this->channels->authorizePresence('acme-room', new User, null));
        $this->assertNull($this->channels->authorizePresence('acme-room', new User, 1));
    }

    /**
     * Realtime's own channels are registered as `[DefaultChannels::class, 'method']`
     * so that DefaultChannels can have its dependencies injected; that only works
     * if the extender resolves the class through the container.
     */
    #[Test]
    public function a_class_method_pair_is_resolved_through_the_container(): void
    {
        $instance = new class {
            public function allow(User $actor, int $id): bool
            {
                return $id === 7;
            }

            public function __invoke(User $actor, int $id): bool
            {
                return $id === 8;
            }
        };

        $extender = (new RealtimeExtender())
            ->privateChannel('acme-method', ['AcmeChannels', 'allow'])
            ->privateChannel('acme-invokable', 'AcmeChannels');

        $this->runExtender($extender, ['AcmeChannels' => $instance]);

        $this->assertTrue($this->channels->authorizePrivate('acme-method', new User, 7));
        $this->assertFalse($this->channels->authorizePrivate('acme-method', new User, 8));
        $this->assertTrue($this->channels->authorizePrivate('acme-invokable', new User, 8));
    }

    #[Test]
    public function daemon_url_throws_on_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new RealtimeExtender())->daemonUrl('https://example.com/some/path');
    }
}
