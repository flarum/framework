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
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RealtimeExtenderTest extends TestCase
{
    private RealtimeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new RealtimeRegistry();
    }

    /**
     * Runs the extender against a fake container that immediately calls the
     * afterResolving callbacks for both Settings and RealtimeRegistry.
     */
    private function runExtender(RealtimeExtender $extender): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('use');

        $container = $this->createMock(Container::class);

        $registry = $this->registry;

        $container
            ->method('afterResolving')
            ->willReturnCallback(function (string $abstract, callable $callback) use ($settings, $registry) {
                if ($abstract === Settings::class) {
                    $callback($settings);
                }

                if ($abstract === RealtimeRegistry::class) {
                    $callback($registry);
                }
            });

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
    public function extender_can_be_chained(): void
    {
        $extender = (new RealtimeExtender())
            ->broadcastModelEvent('EventA', fn ($e) => $e, null, 'nameA')
            ->broadcastModelEvent('EventB', fn ($e) => $e, null, 'nameB')
            ->broadcastDialogEvent('DialogEvent', fn ($e) => $e->message)
            ->broadcastFlagEvent('FlagEvent', fn ($e) => $e->discussion, 'flagged')
            ->registerModelEndpoint('MyModel', 'my-models');

        $this->runExtender($extender);

        $this->assertCount(2, $this->registry->getModelEvents());
        $this->assertCount(1, $this->registry->getDialogEvents());
        $this->assertCount(1, $this->registry->getFlagEvents());
        $this->assertCount(1, $this->registry->getModelEndpoints());
    }

    #[Test]
    public function daemon_url_throws_on_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new RealtimeExtender())->daemonUrl('https://example.com/some/path');
    }
}
