<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

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
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1],
                // Discussion with deleted first post.
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->subDay()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'restricted', 'email' => 'restricted@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 40, 'name_singular' => 'tess', 'name_plural' => 'tess'],
            ],
            'group_user' => [
                ['group_id' => 40, 'user_id' => 3],
            ],
            'group_permission' => [
                ['group_id' => 40, 'permission' => 'discussion.reply'],
            ],
        ]);
    }

    /**
     * @dataProvider discussionRepliesPrvider
     * @test
     */
    public function can_create_reply_if_allowed(int $actorId, int $discussionId, int $responseStatus)
    {
        // Reset permissions for normal users group.
        $this->database()
            ->table('group_permission')
            ->where('permission', 'discussion.reply')
            ->where('group_id', Group::MEMBER_ID)
            ->delete();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $actorId,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => [
                                'data' => ['type' => 'discussions', 'id' => $discussionId]
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals($responseStatus, $response->getStatusCode(), (string) $response->getBody());
    }

    public function discussionRepliesPrvider(): array
    {
        return [
            // [$actorId, $discussionId, $responseStatus]
            'can_create_reply_with_ability' => [3, 1, 201],
            'cannot_create_reply_without_ability' => [2, 1, 403],
            'can_create_reply_with_ability_when_first_post_is_deleted' => [3, 2, 201],
            'cannot_create_reply_without_ability_when_first_post_is_deleted' => [2, 2, 403],
        ];
    }

    /**
     * @test
     */
    public function limited_by_throttler()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'posts',
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

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'posts',
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

        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());
    }
}
