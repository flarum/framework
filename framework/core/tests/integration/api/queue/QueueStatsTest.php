<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\queue;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class QueueStatsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config('queue', ['driver' => 'database']);

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    private function seedJobs(): void
    {
        $now = time();
        $db = $this->app()->getContainer()->make('db.connection');

        $db->table('queue_jobs')->insert([
            // 2 pending on default, 1 reserved (in-flight) on default
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $now, 'created_at' => $now],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $now, 'created_at' => $now],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 1, 'reserved_at' => $now, 'available_at' => $now, 'created_at' => $now],
            // 1 pending on media
            ['queue' => 'media', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $now, 'created_at' => $now],
        ]);

        $db->table('queue_failed_jobs')->insert([
            ['uuid' => 'a', 'connection' => 'flarum', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x'],
            ['uuid' => 'b', 'connection' => 'flarum', 'queue' => 'media', 'payload' => '{}', 'exception' => 'x'],
        ]);
    }

    private function stats(int $actorId = 1): array
    {
        $response = $this->send(
            $this->request('GET', '/api/queue/stats', ['authenticatedAs' => $actorId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    #[Test]
    public function the_endpoint_reports_aggregate_totals()
    {
        $this->seedJobs();

        $stats = $this->stats();

        $this->assertSame(3, $stats['totals']['pending']);   // 2 default + 1 media
        $this->assertSame(1, $stats['totals']['reserved']);  // 1 default in-flight
        $this->assertSame(2, $stats['totals']['failed']);
    }

    #[Test]
    public function the_endpoint_reports_per_queue_counts()
    {
        $this->seedJobs();

        $queues = $this->stats()['queues'];

        $this->assertSame(2, $queues['default']['pending']);
        $this->assertSame(1, $queues['default']['reserved']);
        $this->assertSame(1, $queues['media']['pending']);
        $this->assertSame(0, $queues['media']['reserved']);
    }

    #[Test]
    public function an_empty_queue_reports_zeroes()
    {
        $stats = $this->stats();

        $this->assertSame(['pending' => 0, 'reserved' => 0, 'failed' => 0], $stats['totals']);
        $this->assertSame([], $stats['queues']);
    }

    #[Test]
    public function regular_users_cannot_read_queue_stats()
    {
        $response = $this->send(
            $this->request('GET', '/api/queue/stats', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function the_sync_driver_has_no_queue_stats_endpoint()
    {
        // Fresh app on the sync driver: jobs run inline, there is nothing to
        // report, so the endpoint must not be available.
        $this->config('queue', []);

        $response = $this->send(
            $this->request('GET', '/api/queue/stats', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
