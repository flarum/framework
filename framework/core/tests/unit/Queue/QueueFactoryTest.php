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
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Queue\Queue;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class QueueFactoryTest extends TestCase
{
    /**
     * A queue connection bound by an extension may carry no name — core only
     * names its own driver, and (e.g.) fof/redis replaces the binding without
     * one, so `getConnectionName()` returns null. That null flows into
     * `pause()`, so the pause bookkeeping must tolerate it rather than fatal on
     * a `string` type hint (the recurring `queue:pause` crash).
     */
    #[Test]
    public function pausing_a_connection_with_no_name_does_not_fatal(): void
    {
        $cache = new Repository(new ArrayStore());
        $factory = new QueueFactory(fn () => m::mock(Queue::class), $cache);

        // Previously threw: trackPausedQueue(): Argument #1 must be string, null given.
        $factory->pause(null, 'default');

        $this->assertTrue($factory->isPaused(null, 'default'));
        $this->assertContains('default', $factory->pausedQueues(null));

        $factory->resume(null, 'default');

        $this->assertFalse($factory->isPaused(null, 'default'));
        $this->assertNotContains('default', $factory->pausedQueues(null));
    }

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
