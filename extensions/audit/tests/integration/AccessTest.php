<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class AccessTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->setting('flarum-audit.limitedIpAddress', '0');
        $this->setting('flarum-audit.limitedActions', '');

        $this->prepareDatabase([
            'audit_log' => [
                [
                    'id' => 1,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '192.168.1.20',
                    'action' => 'post.created',
                    'payload' => json_encode([
                        'post_id' => 1234,
                        'discussion_id' => 123,
                    ]),
                    'created_at' => '2022-01-01 12:00:00',
                ],
                [
                    'id' => 2,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '192.168.1.20',
                    'action' => 'setting_changed',
                    'payload' => json_encode([
                        'key' => 'custom_less',
                    ]),
                    'created_at' => '2022-01-01 13:00:00',
                ],
            ],
            User::class => [
                $this->normalUser(),
            ],
            'group_user' => [
                [
                    'user_id' => 2,
                    'group_id' => Group::MODERATOR_ID,
                ],
            ],
            'group_permission' => [
                [
                    'group_id' => Group::MODERATOR_ID,
                    'permission' => 'flarum-audit.viewLimited',
                ],
            ],
        ]);
    }

    protected function jsonApiData(array $options = [])
    {
        $response = $this->send($this->request('GET', '/api/audit', $options));

        $this->assertEquals(200, $response->getStatusCode(), 'Assert request status code');

        $data = json_decode($response->getBody()->getContents(), true);

        return Arr::get($data, 'data');
    }

    #[Test]
    public function noAccess()
    {
        $this->assertEmpty($this->jsonApiData());
    }

    #[Test]
    public function limitedAccess()
    {
        $data = $this->jsonApiData([
            'authenticatedAs' => 2,
        ]);

        $this->assertCount(2, $data);

        $this->assertNull(Arr::get($data[0], 'attributes.ipAddress'));
    }

    #[Test]
    public function limitedAccessWithIp()
    {
        $this->setting('flarum-audit.limitedIpAddress', '1');

        $data = $this->jsonApiData([
            'authenticatedAs' => 2,
        ]);

        $this->assertCount(2, $data);

        $this->assertEquals('192.168.1.20', Arr::get($data[0], 'attributes.ipAddress'));
    }

    #[Test]
    public function limitedAccessFiltered()
    {
        $this->setting('flarum-audit.limitedActions', 'post.*,cache_cleared');

        $data = $this->jsonApiData([
            'authenticatedAs' => 2,
        ]);

        $this->assertCount(1, $data);
    }

    #[Test]
    public function fullAccess()
    {
        $data = $this->jsonApiData([
            'authenticatedAs' => 1,
        ]);

        $this->assertCount(2, $data);

        $this->assertEquals('192.168.1.20', Arr::get($data[0], 'attributes.ipAddress'));
    }
}
