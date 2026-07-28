<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueueResumed;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pausing/resuming a queue is a privileged, operationally significant action
 * (jobs silently stop flowing), so it is audited. Both the admin UI endpoint
 * and the CLI commands funnel through QueueFactory, which dispatches these
 * Illuminate events — so listening to the events captures every pause channel
 * and records the exact queue name that was paused, including named queues
 * added by extensions and the wildcard ("*") used by "pause all".
 */
class CoreQueueTest extends TestCase
{
    private function events(): Dispatcher
    {
        return $this->app()->getContainer()->make(Dispatcher::class);
    }

    #[Test]
    public function pausing_a_queue_is_logged_with_the_queue_name()
    {
        $this->events()->dispatch(new QueuePaused('redis', 'media'));

        $log = AuditLog::query()->where('action', 'queue.paused')->first();

        $this->assertNotNull($log, 'A queue.paused entry should be logged');
        $this->assertSame(['connection' => 'redis', 'queue' => 'media'], $log->payload);
    }

    #[Test]
    public function resuming_a_queue_is_logged_with_the_queue_name()
    {
        $this->events()->dispatch(new QueueResumed('redis', 'media'));

        $log = AuditLog::query()->where('action', 'queue.resumed')->first();

        $this->assertNotNull($log, 'A queue.resumed entry should be logged');
        $this->assertSame(['connection' => 'redis', 'queue' => 'media'], $log->payload);
    }

    #[Test]
    public function a_wildcard_pause_all_is_logged_as_the_wildcard_queue()
    {
        $this->events()->dispatch(new QueuePaused('flarum', '*'));

        $log = AuditLog::query()->where('action', 'queue.paused')->first();

        $this->assertNotNull($log);
        $this->assertSame('*', $log->payload['queue']);
    }
}
