<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class GroupMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'toby', 'email' => 'toby@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><p>One of the <GROUPMENTION groupname="Mods" id="4">@"Mods"#g4</GROUPMENTION> will look at this</p></r>'],
                ['id' => 6, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><p><GROUPMENTION groupname="OldGroupName" id="100">@"OldGroupName"#g100</GROUPMENTION></p></r>'],
                ['id' => 7, 'number' => 4, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><p><GROUPMENTION groupname="OldGroupName" id="11">@"OldGroupName"#g11</GROUPMENTION></p></r>'],
            ],
            'post_mentions_group' => [
                ['post_id' => 4, 'mentions_group_id' => 4],
                ['post_id' => 7, 'mentions_group_id' => 11],
            ],
            'group_user' => [
                ['group_id' => 9, 'user_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
                ['group_id' => 9, 'permission' => 'mentionGroups'],
            ],
            Group::class => [
                ['id' => 9, 'name_singular' => 'HasPermissionToMentionGroups', 'name_plural' => 'test'],
                ['id' => 10, 'name_singular' => 'Hidden', 'name_plural' => 'Ninjas', 'icon' => 'fas fa-wrench', 'color' => '#000', 'is_hidden' => 1],
                ['id' => 11, 'name_singular' => 'Fresh Name', 'name_plural' => 'Fresh Name', 'color' => '#ccc', 'icon' => 'fas fa-users', 'is_hidden' => 0]
            ]
        ]);
    }

    /**
     * @test
     */
    public function rendering_a_valid_group_mention_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4')
        );

        $contents = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $contents);

        $response = json_decode($contents, true);

        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('#80349E', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsGroups->find(4));
    }

    /**
     * @test
     */
    public function mentioning_an_invalid_group_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"InvalidGroup"#g99',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ]
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@"InvalidGroup"#g99', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function deleted_group_mentions_render_with_deleted_label()
    {
        $deleted_text = $this->app()->getContainer()->make('translator')->trans('flarum-mentions.forum.group_mention.deleted_text');

        $response = $this->send(
            $this->request('GET', '/api/posts/6', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString("@$deleted_text", $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('GroupMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('@OldGroupName', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function group_mentions_render_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/7', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@Fresh Name', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('@OldGroupName', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsGroups->find(11));
    }

    /**
     * @test
     */
    public function mentioning_a_group_as_an_admin_user_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@Mods', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('fas fa-bolt', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Mods"#g4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function mentioning_multiple_groups_as_an_admin_user_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Admins"#g1 @"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@Admins', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@Mods', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('fas fa-wrench', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('fas fa-bolt', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Admins"#g1 @"Mods"#g4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(2, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function mentioning_a_virtual_group_as_an_admin_user_does_not_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Members"#g3 @"Guests"#g2',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ]
                    ]
                ]
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        $response = json_decode($body, true);

        $this->assertStringNotContainsString('@Members', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('@Guests', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Members"#g3 @"Guests"#g2', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function regular_user_does_not_have_group_mention_permission_by_default()
    {
        $this->database();
        $this->assertFalse(User::find(3)->can('mentionGroups'));
    }

    /**
     * @test
     */
    public function regular_user_does_have_group_mention_permission_when_added()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'mentionGroups'],
            ]
        ]);

        $this->database();
        $this->assertTrue(User::find(3)->can('mentionGroups'));
    }

    /**
     * @test
     */
    public function user_without_permission_cannot_mention_groups()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringNotContainsString('@Mods', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"Mods"#g4', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function user_with_permission_can_mention_groups()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@Mods', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"Mods"#g4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function user_with_permission_cannot_mention_hidden_groups()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Ninjas"#g10',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringNotContainsString('@Ninjas', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"Ninjas"#g10', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsGroups);
    }

    /**
     * @test
     */
    public function editing_a_post_that_has_a_mention_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/4', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => 'New content with @"Mods"#g4 mention',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@Mods', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('New content with @"Mods"#g4 mention', $response['data']['attributes']['content']);
        $this->assertStringContainsString('GroupMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsGroups->find(4));
    }
}
