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

class FailedJobsTest extends TestCase
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

    private function seedFailed(): void
    {
        $db = $this->app()->getContainer()->make('db.connection');

        // A realistic payload whose data.command is a serialized object, as the
        // retry path unserializes it to refresh attempts / retry-until.
        $payload = json_encode([
            'uuid' => '11111111-1111-4111-8111-111111111111',
            'displayName' => 'App\\Jobs\\ExampleJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => ['commandName' => 'stdClass', 'command' => serialize(new \stdClass())],
        ]);

        $db->table('queue_failed_jobs')->insert([
            [
                'uuid' => '11111111-1111-4111-8111-111111111111',
                'connection' => 'flarum',
                'queue' => 'default',
                'payload' => $payload,
                'exception' => "RuntimeException: something broke\n#0 /app/Foo.php(12): bar()",
                'failed_at' => '2026-01-01 00:00:00',
            ],
            [
                'uuid' => '22222222-2222-4222-8222-222222222222',
                'connection' => 'flarum',
                'queue' => 'media',
                'payload' => $payload,
                'exception' => 'LogicException: nope',
                'failed_at' => '2026-01-02 00:00:00',
            ],
        ]);
    }

    #[Test]
    public function admins_can_list_failed_jobs_with_the_exception()
    {
        $this->seedFailed();

        $response = $this->send($this->request('GET', '/api/queue/failed', ['authenticatedAs' => 1]));
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $jobs = $body['data'];

        $this->assertCount(2, $jobs);

        $first = collect($jobs)->firstWhere('id', '11111111-1111-4111-8111-111111111111');
        $this->assertSame('default', $first['queue']);
        $this->assertSame('App\\Jobs\\ExampleJob', $first['name']);
        $this->assertStringContainsString('something broke', $first['exception']);
    }

    #[Test]
    public function regular_users_cannot_list_failed_jobs()
    {
        $this->seedFailed();

        $response = $this->send($this->request('GET', '/api/queue/failed', ['authenticatedAs' => 2]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function retrying_a_failed_job_requeues_it_and_removes_the_record()
    {
        $this->seedFailed();

        $db = $this->app()->getContainer()->make('db.connection');

        $response = $this->send($this->request('POST', '/api/queue/failed/11111111-1111-4111-8111-111111111111/retry', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());

        // Record gone from the failed table...
        $this->assertSame(0, $db->table('queue_failed_jobs')->where('uuid', '11111111-1111-4111-8111-111111111111')->count());
        $this->assertSame(1, $db->table('queue_failed_jobs')->count());

        // ...and pushed back onto its queue.
        $this->assertSame(1, $db->table('queue_jobs')->where('queue', 'default')->count());
    }

    #[Test]
    public function forgetting_a_failed_job_removes_it_without_requeueing()
    {
        $this->seedFailed();

        $db = $this->app()->getContainer()->make('db.connection');

        $response = $this->send($this->request('DELETE', '/api/queue/failed/22222222-2222-4222-8222-222222222222', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(0, $db->table('queue_failed_jobs')->where('uuid', '22222222-2222-4222-8222-222222222222')->count());
        $this->assertSame(1, $db->table('queue_failed_jobs')->count());
        $this->assertSame(0, $db->table('queue_jobs')->count());
    }

    #[Test]
    public function retry_all_requeues_every_failed_job()
    {
        $this->seedFailed();

        $db = $this->app()->getContainer()->make('db.connection');

        $response = $this->send($this->request('POST', '/api/queue/failed/retry', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(0, $db->table('queue_failed_jobs')->count());
        $this->assertSame(2, $db->table('queue_jobs')->count());
    }

    #[Test]
    public function regular_users_cannot_retry_or_forget()
    {
        $this->seedFailed();

        $retry = $this->send($this->request('POST', '/api/queue/failed/11111111-1111-4111-8111-111111111111/retry', ['authenticatedAs' => 2]));
        $forget = $this->send($this->request('DELETE', '/api/queue/failed/11111111-1111-4111-8111-111111111111', ['authenticatedAs' => 2]));

        $this->assertEquals(403, $retry->getStatusCode());
        $this->assertEquals(403, $forget->getStatusCode());
    }
}
