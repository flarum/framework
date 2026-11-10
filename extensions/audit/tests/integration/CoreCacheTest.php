<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use Flarum\Foundation\Event\ClearingCache;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class CoreCacheTest extends TestCase
{
    #[Test]
    public function cache_clear_is_logged()
    {
        // Dispatch the event our listener hooks directly, rather than driving the /api/cache
        // controller, which also runs assets:publish — a step that isn't reliably available in CI.
        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new ClearingCache());

        $log = AuditLog::query()->where('action', 'cache_cleared')->first();

        $this->assertNotNull($log, 'A cache_cleared entry should be logged');
        // Dispatched outside an HTTP request, so there is no actor or IP context.
        $this->assertNull($log->actor_id);
        $this->assertNull($log->payload);
    }
}
