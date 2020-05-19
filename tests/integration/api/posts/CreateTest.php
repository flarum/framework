<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class CreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_create_reply()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function limited_by_throttler()
    {
        $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => 'Second reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(429, $response->getStatusCode());
    }
}
