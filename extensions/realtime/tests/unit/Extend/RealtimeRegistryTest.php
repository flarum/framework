<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Extend;

use Flarum\Realtime\Push\RealtimeRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RealtimeRegistryTest extends TestCase
{
    private RealtimeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new RealtimeRegistry();
    }

    #[Test]
    public function it_starts_empty(): void
    {
        $this->assertSame([], $this->registry->getModelEvents());
        $this->assertSame([], $this->registry->getDialogEvents());
        $this->assertSame([], $this->registry->getFlagEvents());
        $this->assertSame([], $this->registry->getModelEndpoints());
    }

    #[Test]
    public function it_registers_a_model_event(): void
    {
        $getModel = fn ($e) => $e->post;
        $getActor = fn ($e) => $e->user;

        $this->registry->addModelEvent(
            ['Flarum\\Likes\\Event\\PostWasLiked'],
            $getModel,
            $getActor,
            'likesMutation'
        );

        $events = $this->registry->getModelEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['Flarum\\Likes\\Event\\PostWasLiked'], $events[0]['events']);
        $this->assertSame($getModel, $events[0]['getModel']);
        $this->assertSame($getActor, $events[0]['getActor']);
        $this->assertSame('likesMutation', $events[0]['eventName']);
    }

    #[Test]
    public function it_registers_a_model_event_with_multiple_event_classes(): void
    {
        $this->registry->addModelEvent(
            ['EventA', 'EventB'],
            fn ($e) => $e->post,
            null,
            'myEvent'
        );

        $events = $this->registry->getModelEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['EventA', 'EventB'], $events[0]['events']);
        $this->assertNull($events[0]['getActor']);
    }

    #[Test]
    public function it_registers_a_model_event_without_actor_or_event_name(): void
    {
        $this->registry->addModelEvent(['SomeEvent'], fn ($e) => $e->model);

        $events = $this->registry->getModelEvents();

        $this->assertNull($events[0]['getActor']);
        $this->assertNull($events[0]['eventName']);
    }

    #[Test]
    public function it_accumulates_multiple_model_events(): void
    {
        $this->registry->addModelEvent(['EventA'], fn ($e) => $e, null, 'nameA');
        $this->registry->addModelEvent(['EventB'], fn ($e) => $e, null, 'nameB');
        $this->registry->addModelEvent(['EventC'], fn ($e) => $e, null, 'nameC');

        $this->assertCount(3, $this->registry->getModelEvents());
    }

    #[Test]
    public function it_registers_a_dialog_event(): void
    {
        $getMessage = fn ($e) => $e->message;

        $this->registry->addDialogEvent(['Flarum\\Messages\\DialogMessage\\Event\\Created'], $getMessage);

        $events = $this->registry->getDialogEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['Flarum\\Messages\\DialogMessage\\Event\\Created'], $events[0]['events']);
        $this->assertSame($getMessage, $events[0]['getMessage']);
    }

    #[Test]
    public function it_registers_a_flag_event(): void
    {
        $getDiscussion = fn ($e) => $e->flag->post->discussion;

        $this->registry->addFlagEvent(
            ['Flarum\\Flags\\Event\\Created', 'Flarum\\Flags\\Event\\Deleting'],
            $getDiscussion,
            'flagged'
        );

        $events = $this->registry->getFlagEvents();

        $this->assertCount(1, $events);
        $this->assertSame(['Flarum\\Flags\\Event\\Created', 'Flarum\\Flags\\Event\\Deleting'], $events[0]['events']);
        $this->assertSame($getDiscussion, $events[0]['getDiscussion']);
        $this->assertSame('flagged', $events[0]['eventName']);
    }

    #[Test]
    public function it_registers_a_model_endpoint(): void
    {
        $this->registry->addModelEndpoint('Flarum\\Messages\\Dialog', 'dialogs');
        $this->registry->addModelEndpoint('Flarum\\Messages\\DialogMessage', 'dialog-messages');

        $endpoints = $this->registry->getModelEndpoints();

        $this->assertSame('dialogs', $endpoints['Flarum\\Messages\\Dialog']);
        $this->assertSame('dialog-messages', $endpoints['Flarum\\Messages\\DialogMessage']);
    }

    #[Test]
    public function it_overwrites_a_model_endpoint_when_registered_twice(): void
    {
        $this->registry->addModelEndpoint('Flarum\\Post\\Post', 'posts');
        $this->registry->addModelEndpoint('Flarum\\Post\\Post', 'custom-posts');

        $this->assertSame('custom-posts', $this->registry->getModelEndpoints()['Flarum\\Post\\Post']);
    }
}
