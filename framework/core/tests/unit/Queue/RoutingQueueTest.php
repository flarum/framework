<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Queue;

use Flarum\Queue\AbstractJob;
use Flarum\Queue\RoutingQueue;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueRoutes;
use PHPUnit\Framework\Attributes\Test;

class RoutingQueueTest extends TestCase
{
    private function recordingQueue(): Queue
    {
        return new class() implements Queue {
            /** @var array<int, array{method: string, job: mixed, queue: mixed}> */
            public array $pushed = [];

            public function push($job, $data = '', $queue = null)
            {
                $this->pushed[] = ['method' => 'push', 'job' => $job, 'queue' => $queue];

                return null;
            }

            public function later($delay, $job, $data = '', $queue = null)
            {
                $this->pushed[] = ['method' => 'later', 'job' => $job, 'queue' => $queue];

                return null;
            }

            public function bulk($jobs, $data = '', $queue = null)
            {
                foreach ((array) $jobs as $job) {
                    $this->pushed[] = ['method' => 'bulk', 'job' => $job, 'queue' => $queue];
                }

                return null;
            }

            public function pushOn($queue, $job, $data = '')
            {
                return $this->push($job, $data, $queue);
            }

            public function laterOn($queue, $delay, $job, $data = '')
            {
                return $this->later($delay, $job, $data, $queue);
            }

            public function pushRaw($payload, $queue = null, array $options = [])
            {
                return null;
            }

            public function pop($queue = null)
            {
                return null;
            }

            public function size($queue = null)
            {
                return 0;
            }

            public function pendingSize($queue = null)
            {
                return 0;
            }

            public function delayedSize($queue = null)
            {
                return 0;
            }

            public function reservedSize($queue = null)
            {
                return 0;
            }

            public function creationTimeOfOldestPendingJob($queue = null)
            {
                return null;
            }

            public function getConnectionName()
            {
                return 'test';
            }

            public function setConnectionName($name)
            {
                return $this;
            }
        };
    }

    /**
     * @param array<class-string, string> $routes
     */
    private function routing(Queue $inner, array $routes): RoutingQueue
    {
        $manager = new QueueRoutes();

        foreach ($routes as $class => $queue) {
            $manager->set($class, $queue);
        }

        return new RoutingQueue($inner, $manager);
    }

    #[Test]
    public function routes_a_registered_job_class_when_no_queue_is_given(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [RoutingQueueTestJobA::class => 'gdpr']);

        $queue->push(new RoutingQueueTestJobA());

        $this->assertSame('gdpr', $inner->pushed[0]['queue']);
    }

    #[Test]
    public function an_explicit_queue_always_wins(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [RoutingQueueTestJobA::class => 'gdpr']);

        $queue->push(new RoutingQueueTestJobA(), '', 'explicit');

        $this->assertSame('explicit', $inner->pushed[0]['queue']);
    }

    #[Test]
    public function an_unregistered_job_passes_through_with_null_queue(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [RoutingQueueTestJobA::class => 'gdpr']);

        $queue->push(new RoutingQueueTestJobB());

        $this->assertNull($inner->pushed[0]['queue']);
    }

    #[Test]
    public function sibling_job_classes_route_independently(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [
            RoutingQueueTestJobA::class => 'realtime',
            RoutingQueueTestJobB::class => 'gdpr',
        ]);

        $queue->push(new RoutingQueueTestJobA());
        $queue->push(new RoutingQueueTestJobB());

        $this->assertSame('realtime', $inner->pushed[0]['queue']);
        $this->assertSame('gdpr', $inner->pushed[1]['queue']);
    }

    #[Test]
    public function later_is_routed_too(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [RoutingQueueTestJobA::class => 'gdpr']);

        $queue->later(60, new RoutingQueueTestJobA());

        $this->assertSame('gdpr', $inner->pushed[0]['queue']);
    }

    #[Test]
    public function a_string_job_name_is_passed_through_untouched(): void
    {
        // Jobs pushed by class-string name (not an object) can't be routed by
        // instance; they must pass through rather than error.
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [RoutingQueueTestJobA::class => 'gdpr']);

        $queue->push('some-string-job');

        $this->assertNull($inner->pushed[0]['queue']);
    }

    #[Test]
    public function non_routing_methods_delegate(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, []);

        $this->assertSame('test', $queue->getConnectionName());
        $this->assertSame(0, $queue->size());
    }

    #[Test]
    public function a_route_on_an_abstract_base_covers_its_subclasses(): void
    {
        $inner = $this->recordingQueue();
        // Route the abstract base; both concrete subclasses inherit it.
        $queue = $this->routing($inner, [RoutingQueueTestBase::class => 'gdpr']);

        $queue->push(new RoutingQueueTestChildA());
        $queue->push(new RoutingQueueTestChildB());

        $this->assertSame('gdpr', $inner->pushed[0]['queue']);
        $this->assertSame('gdpr', $inner->pushed[1]['queue']);
    }

    #[Test]
    public function the_most_specific_route_wins(): void
    {
        $inner = $this->recordingQueue();
        $queue = $this->routing($inner, [
            RoutingQueueTestBase::class => 'gdpr',
            RoutingQueueTestChildA::class => 'exports',
        ]);

        // Child A has its own route; Child B falls back to the base's.
        $queue->push(new RoutingQueueTestChildA());
        $queue->push(new RoutingQueueTestChildB());

        $this->assertSame('exports', $inner->pushed[0]['queue']);
        $this->assertSame('gdpr', $inner->pushed[1]['queue']);
    }
}

class RoutingQueueTestJobA extends AbstractJob
{
}

class RoutingQueueTestJobB extends AbstractJob
{
}

abstract class RoutingQueueTestBase extends AbstractJob
{
}

class RoutingQueueTestChildA extends RoutingQueueTestBase
{
}

class RoutingQueueTestChildB extends RoutingQueueTestBase
{
}
