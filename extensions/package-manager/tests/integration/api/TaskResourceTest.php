<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\ExtensionManager\Task\Task;
use Flarum\ExtensionManager\Tests\integration\TestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TaskResourceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Task::class => [
                [
                    'id' => 1,
                    'status' => Task::SUCCESS,
                    'operation' => Task::UPDATE_CHECK,
                    'command' => 'composer outdated',
                    'package' => null,
                    'output' => 'flarum/core 1.0.0',
                    'created_at' => '2024-01-01 00:00:00',
                    'started_at' => '2024-01-01 00:00:01',
                    'finished_at' => '2024-01-01 00:00:02',
                    'peak_memory_used' => 1024,
                ],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_list_tasks()
    {
        $response = $this->send(
            $this->request('GET', '/api/extension-manager-tasks')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_list_tasks()
    {
        $response = $this->send(
            $this->request('GET', '/api/extension-manager-tasks', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_tasks()
    {
        $response = $this->send(
            $this->request('GET', '/api/extension-manager-tasks', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertNotEmpty($body['data']);
        $this->assertEquals('extension-manager-tasks', $body['data'][0]['type']);
    }
}
