<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Queue;

use Flarum\Queue\QueueFactory;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Queue\Queue;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class QueueFactoryTest extends TestCase
{
    #[Test]
    public function connection_resolves_and_caches_the_queue_from_the_factory_callback(): void
    {
        $queue = m::mock(Queue::class);

        $calls = 0;
        $factory = new QueueFactory(function () use ($queue, &$calls) {
            $calls++;

            return $queue;
        });

        $this->assertSame($queue, $factory->connection());
        // Resolving again returns the cached instance without re-invoking the callback.
        $this->assertSame($queue, $factory->connection());
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function is_paused_always_reports_false(): void
    {
        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));
    }

    #[Test]
    public function get_paused_queues_is_always_empty(): void
    {
        $this->assertSame([], $this->factory()->getPausedQueues('flarum', ['default', 'high']));
    }

    /**
     * The Queue Pause/Resume manager methods Flarum does not support must be
     * callable no-ops, so a queue worker invoking them can never crash with a
     * "Call to undefined method".
     */
    #[Test]
    public function pause_family_methods_are_callable_no_ops(): void
    {
        $factory = $this->factory();

        $this->assertNull($factory->pause('flarum', 'default'));
        $this->assertNull($factory->pauseFor('flarum', 'default', 60));
        $this->assertNull($factory->resume('flarum', 'default'));
        $this->assertNull($factory->withoutInterruptionPolling());
    }

    private function factory(): QueueFactory
    {
        return new QueueFactory(fn () => m::mock(Queue::class));
    }
}
