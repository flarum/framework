<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class StickyDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-sticky');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'Muralf_', 'email' => 'muralf_@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(2), 'last_posted_at' => Carbon::now()->addMinutes(5), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(3), 'last_posted_at' => Carbon::now()->addMinute(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(4), 'last_posted_at' => Carbon::now()->addMinutes(2), 'user_id' => 1, 'first_post_id' => 4, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'number' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Group', 'name_plural' => 'Groups', 'color' => 'blue'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 5]
            ],
            'group_permission' => [
                ['group_id' => 5, 'permission' => 'discussion.sticky'],
            ],
        ]);
    }

    /**
     * @dataProvider stickyDataProvider
     * @test
     */
    public function can_sticky_if_allowed(int $actorId, bool $allowed, bool $sticky)
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => $actorId,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isSticky' => $sticky
                        ]
                    ]
                ]
            ])
        );

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        if ($allowed) {
            $this->assertEquals(200, $response->getStatusCode(), $body);
            $this->assertEquals($sticky, $json['data']['attributes']['isSticky']);
        } else {
            $this->assertEquals(403, $response->getStatusCode(), $body);
        }
    }

    public static function stickyDataProvider(): array
    {
        return [
            [1, true, true],
            [1, true, false],
            [2, true, true],
            [2, true, false],
            [3, false, true],
            [3, false, false],
        ];
    }
}
