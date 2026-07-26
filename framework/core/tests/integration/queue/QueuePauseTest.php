<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Queue\AbstractJob;
use Flarum\Queue\QueueFactory;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueueResumed;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Queue pausing, backed by the shared cache store so the signal reaches
 * every worker process — including ones in other containers, and Horizon
 * workers, which consult the same manager surface when popping jobs.
 *
 * The cache key format is Illuminate's own
 * (`illuminate:queue:paused:{connection}:{queue}`), so anything speaking
 * the upstream convention interoperates.
 */
class QueuePauseTest extends ConsoleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->config('queue', ['driver' => 'database']);
    }

    private function factory(): QueueFactory
    {
        $factory = $this->app()->getContainer()->make(Factory::class);

        $this->assertInstanceOf(QueueFactory::class, $factory);

        return $factory;
    }

    #[Test]
    public function pausing_marks_the_queue_paused_in_the_shared_cache()
    {
        $factory = $this->factory();

        $this->assertFalse($factory->isPaused('flarum', 'default'));

        $factory->pause('flarum', 'default');

        $this->assertTrue($factory->isPaused('flarum', 'default'));

        // The exact upstream key format, for interoperability with anything
        // built against Illuminate's own convention (e.g. Horizon tooling).
        $this->assertTrue(
            (bool) $this->app()->getContainer()->make('cache.store')->get('illuminate:queue:paused:flarum:default')
        );

        $factory->resume('flarum', 'default');
    }

    #[Test]
    public function resuming_clears_the_pause()
    {
        $factory = $this->factory();

        $factory->pause('flarum', 'default');
        $factory->resume('flarum', 'default');

        $this->assertFalse($factory->isPaused('flarum', 'default'));
    }

    #[Test]
    public function paused_queues_are_filtered_from_a_queue_list()
    {
        $factory = $this->factory();

        $factory->pause('flarum', 'media');

        $this->assertSame(['media'], $factory->getPausedQueues('flarum', ['default', 'media']));

        $factory->resume('flarum', 'media');
    }

    #[Test]
    public function a_worker_does_not_pop_jobs_from_a_paused_queue()
    {
        $container = $this->app()->getContainer();
        $db = $container->make('db.connection');

        PauseProbeJob::$ran = false;
        $container->make(Queue::class)->push(new PauseProbeJob());

        $this->assertSame(1, $db->table('queue_jobs')->count());

        $factory = $this->factory();
        $factory->pause('flarum', 'default');

        $worker = $container->make(Worker::class);
        $worker->runNextJob('flarum', 'default', new WorkerOptions(sleep: 0));

        $this->assertFalse(PauseProbeJob::$ran, 'A paused queue must not have jobs popped from it.');
        $this->assertSame(1, $db->table('queue_jobs')->count(), 'The job must remain queued while paused.');

        $factory->resume('flarum', 'default');
        $worker->runNextJob('flarum', 'default', new WorkerOptions(sleep: 0));

        $this->assertTrue(PauseProbeJob::$ran, 'After resuming, the job must be processed.');
        $this->assertSame(0, $db->table('queue_jobs')->count());
    }

    #[Test]
    public function pause_and_resume_dispatch_events()
    {
        $container = $this->app()->getContainer();
        $events = $container->make('events');

        $seen = [];
        $events->listen(QueuePaused::class, function () use (&$seen) {
            $seen[] = 'paused';
        });
        $events->listen(QueueResumed::class, function () use (&$seen) {
            $seen[] = 'resumed';
        });

        $factory = $this->factory();
        $factory->pause('flarum', 'default');
        $factory->resume('flarum', 'default');

        $this->assertSame(['paused', 'resumed'], $seen);
    }

    #[Test]
    public function the_pause_and_resume_commands_toggle_the_state()
    {
        $this->runCommand(['command' => 'queue:pause', 'queue' => 'default']);

        $this->assertTrue($this->factory()->isPaused('flarum', 'default'));

        $this->runCommand(['command' => 'queue:resume', 'queue' => 'default']);

        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));
    }

    #[Test]
    public function paused_queues_are_tracked_per_connection_for_the_dashboard()
    {
        $factory = $this->factory();

        $this->assertSame([], $factory->pausedQueues('flarum'));

        $factory->pause('flarum', 'default');
        $factory->pause('flarum', 'media');

        $this->assertSame(['default', 'media'], $factory->pausedQueues('flarum'));

        $factory->resume('flarum', 'media');

        $this->assertSame(['default'], $factory->pausedQueues('flarum'));

        $factory->resume('flarum', 'default');

        $this->assertSame([], $factory->pausedQueues('flarum'));
    }

    #[Test]
    public function the_paused_queue_list_self_heals_when_a_pause_expires()
    {
        $factory = $this->factory();

        $factory->pause('flarum', 'default');

        // Simulate a pauseFor() TTL expiring (or an external resume through
        // the raw Illuminate cache key): the list must not report queues
        // that are no longer actually paused.
        $this->app()->getContainer()->make('cache.store')->forget('illuminate:queue:paused:flarum:default');

        $this->assertSame([], $factory->pausedQueues('flarum'));
    }

    #[Test]
    public function the_commands_default_to_the_default_queue()
    {
        $this->runCommand(['command' => 'queue:pause']);

        $this->assertTrue($this->factory()->isPaused('flarum', 'default'));

        $this->runCommand(['command' => 'queue:resume']);

        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));
    }

    #[Test]
    public function pausing_all_queues_pauses_any_queue_name()
    {
        $factory = $this->factory();

        $factory->pause('flarum', '*');

        // Every queue name — including ones core cannot enumerate — reads as
        // paused, so workers stop popping regardless of what they consume.
        $this->assertTrue($factory->isPaused('flarum', 'default'));
        $this->assertTrue($factory->isPaused('flarum', 'media'));
        $this->assertSame(['default', 'media'], $factory->getPausedQueues('flarum', ['default', 'media']));
        $this->assertSame(['*'], $factory->pausedQueues('flarum'));

        $factory->resume('flarum', '*');

        $this->assertFalse($factory->isPaused('flarum', 'default'));
    }

    #[Test]
    public function resuming_all_does_not_clear_individual_pauses()
    {
        $factory = $this->factory();

        $factory->pause('flarum', 'media');
        $factory->pause('flarum', '*');
        $factory->resume('flarum', '*');

        // The layers are orthogonal: an individually paused queue stays
        // paused after a resume-all.
        $this->assertTrue($factory->isPaused('flarum', 'media'));
        $this->assertFalse($factory->isPaused('flarum', 'default'));

        $factory->resume('flarum', 'media');
    }

    #[Test]
    public function the_commands_support_pausing_and_resuming_all_queues()
    {
        $this->runCommand(['command' => 'queue:pause', '--all' => true]);

        $this->assertTrue($this->factory()->isPaused('flarum', 'anything'));

        $this->runCommand(['command' => 'queue:resume', '--all' => true]);

        $this->assertFalse($this->factory()->isPaused('flarum', 'anything'));
    }

    #[Test]
    public function the_known_queue_registry_defaults_to_the_default_queue_and_is_extendable()
    {
        $container = $this->app()->getContainer();

        $this->assertSame(['default'], $container->make('flarum.queue.queues'));
    }
}

class PauseProbeJob extends AbstractJob
{
    public static bool $ran = false;

    public function handle(): void
    {
        static::$ran = true;
    }
}
