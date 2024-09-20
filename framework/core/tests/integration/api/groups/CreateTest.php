<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class CreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function admin_cannot_create_group_without_data()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups', [
                'authenticatedAs' => 1,
                'json' => [],
            ])
        );

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function admin_can_create_group()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'groups',
                        'attributes' => [
                            'nameSingular' => 'flarumite',
                            'namePlural' => 'flarumites',
                            'icon' => 'test',
                            'color' => null,
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        // Verify API response body
        $data = json_decode($body, true);
        $this->assertEquals('flarumite', Arr::get($data, 'data.attributes.nameSingular'));
        $this->assertEquals('flarumites', Arr::get($data, 'data.attributes.namePlural'));
        $this->assertEquals('test', Arr::get($data, 'data.attributes.icon'));
        $this->assertNull(Arr::get($data, 'data.attributes.color'));

        // Verify database entry
        $group = Group::where('icon', 'test')->firstOrFail();
        $this->assertEquals('flarumite', $group->name_singular);
        $this->assertEquals('flarumites', $group->name_plural);
        $this->assertEquals('test', $group->icon);
        $this->assertNull($group->color);
    }

    #[Test]
    public function normal_user_cannot_create_group()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'groups',
                        'attributes' => [
                            'nameSingular' => 'flarumite',
                            'namePlural' => 'flarumites',
                            'icon' => 'test',
                            'color' => null,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
