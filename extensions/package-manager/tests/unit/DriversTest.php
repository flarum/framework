<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\StepJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * 🚨 The rule these protect: a queue makes an update carry on in the background,
 * and is never what makes it work.
 *
 * Every failure here has to fall back to polling rather than refuse the update.
 * Refusing work because a background system nobody asked about is unhappy is
 * precisely the behaviour being replaced — it is why one dead the previous Extension Manager
 * job blocks every later update forever.
 *
 * Mocks rather than hand-written stubs: these interfaces gain methods between
 * Laravel releases, and a stub that has to be chased every time tests less than
 * it costs.
 */
class DriversTest extends TestCase
{
    private function queue(mixed $connection): QueueFactory
    {
        $q = $this->createStub(QueueFactory::class);

        if ($connection instanceof \Throwable) {
            $q->method('connection')->willThrowException($connection);
        } else {
            $q->method('connection')->willReturn($connection);
        }

        return $q;
    }

    public function test_flarums_default_sync_queue_is_not_treated_as_a_worker(): void
    {
        /*
         * 🚨 SyncQueue runs a "queued" job inline in the request that dispatched
         * it. Treating that as a worker would produce exactly the long request
         * this whole design avoids — on the hosts least able to survive one.
         */
        $bus = $this->createMock(Dispatcher::class);
        $bus->expects($this->never())->method('dispatch');

        $drivers = new Drivers($this->queue(new SyncQueue()), $bus);

        $this->assertFalse($drivers->hasWorker());
        $this->assertFalse($drivers->nudge('r1'));
    }

    public function test_a_real_queue_gets_a_job(): void
    {
        $bus = $this->createMock(Dispatcher::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(StepJob::class));

        $drivers = new Drivers($this->queue(new stdClass()), $bus);

        $this->assertTrue($drivers->hasWorker());
        $this->assertTrue($drivers->nudge('r1'));
    }

    public function test_a_misconfigured_queue_falls_back_instead_of_failing_the_update(): void
    {
        $drivers = new Drivers(
            $this->queue(new RuntimeException('no connection')),
            $this->createStub(Dispatcher::class)
        );

        $this->assertFalse($drivers->hasWorker());
        $this->assertFalse($drivers->nudge('r1'), 'a broken queue must not stop an update');
    }

    public function test_a_dispatch_that_throws_is_survivable(): void
    {
        // The update is already running and its state is on disk; a failed
        // dispatch costs a background helper, not the update.
        $bus = $this->createStub(Dispatcher::class);
        $bus->method('dispatch')->willThrowException(new RuntimeException('queue is down'));

        $drivers = new Drivers($this->queue(new stdClass()), $bus);

        $this->assertFalse($drivers->nudge('r1'));
    }

    public function test_a_sync_queue_hidden_inside_flarums_wrapper_is_still_recognised(): void
    {
        /*
         * 🚨 The bug this test exists for, found by running against a real forum
         * rather than by reading the code: Flarum hands back a RoutingQueue that
         * WRAPS the real driver, so `instanceof SyncQueue` on it is always false
         * — including on a stock install where the thing inside is exactly that.
         * The result was "a worker is available" on a forum with none.
         */
        $wrapper = new class(new SyncQueue()) {
            public function __construct(private mixed $driver)
            {
            }

            public function getDriver(): mixed
            {
                return $this->driver;
            }
        };

        $bus = $this->createMock(Dispatcher::class);
        $bus->expects($this->never())->method('dispatch');

        $drivers = new Drivers($this->queue($wrapper), $bus);

        $this->assertFalse($drivers->hasWorker(), 'a sync queue behind a wrapper is still a sync queue');
        $this->assertFalse($drivers->nudge('r1'));
    }

    public function test_a_real_driver_behind_the_wrapper_is_a_worker(): void
    {
        $wrapper = new class(new stdClass()) {
            public function __construct(private mixed $driver)
            {
            }

            public function getDriver(): mixed
            {
                return $this->driver;
            }
        };

        $drivers = new Drivers($this->queue($wrapper), $this->createStub(Dispatcher::class));

        $this->assertTrue($drivers->hasWorker());
    }

    public function test_it_tells_the_admin_which_situation_they_are_in(): void
    {
        $bus = $this->createStub(Dispatcher::class);

        $withWorker = new Drivers($this->queue(new stdClass()), $bus);
        $without = new Drivers($this->queue(new SyncQueue()), $bus);

        $this->assertStringContainsString('close this page', $withWorker->describe());
        $this->assertStringContainsString('keep this page open', $without->describe());
    }
}
