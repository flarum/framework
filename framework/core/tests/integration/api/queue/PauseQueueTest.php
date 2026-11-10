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
use Illuminate\Contracts\Queue\Factory;
use PHPUnit\Framework\Attributes\Test;

class PauseQueueTest extends TestCase
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

    private function factory(): Factory
    {
        return $this->app()->getContainer()->make(Factory::class);
    }

    #[Test]
    public function admins_can_pause_and_resume_the_queue()
    {
        $response = $this->send(
            $this->request('POST', '/api/queue/pause', [
                'authenticatedAs' => 1,
                'json' => ['paused' => true],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($this->factory()->isPaused('flarum', 'default'));

        $response = $this->send(
            $this->request('POST', '/api/queue/pause', [
                'authenticatedAs' => 1,
                'json' => ['paused' => false],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));
    }

    #[Test]
    public function a_specific_queue_can_be_paused()
    {
        $response = $this->send(
            $this->request('POST', '/api/queue/pause', [
                'authenticatedAs' => 1,
                'json' => ['paused' => true, 'queue' => 'media'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrue($this->factory()->isPaused('flarum', 'media'));
        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));

        $this->factory()->resume('flarum', 'media');
    }

    #[Test]
    public function the_sync_driver_cannot_be_paused()
    {
        // Fresh app without the database driver config: sync jobs execute
        // inline and never pass through the worker, so pausing is meaningless
        // and the endpoint must refuse rather than pretend.
        $this->config('queue', []);

        $response = $this->send(
            $this->request('POST', '/api/queue/pause', [
                'authenticatedAs' => 1,
                'json' => ['paused' => true],
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    #[Test]
    public function regular_users_cannot_pause_the_queue()
    {
        $response = $this->send(
            $this->request('POST', '/api/queue/pause', [
                'authenticatedAs' => 2,
                'json' => ['paused' => true],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($this->factory()->isPaused('flarum', 'default'));
    }
}
