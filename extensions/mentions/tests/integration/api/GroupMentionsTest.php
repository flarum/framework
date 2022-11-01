<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\CommentPost;
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
            'users' => [
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'toby', 'email' => 'toby@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'bad_user', 'email' => 'bad_user@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><p>One of the <GROUPMENTION color="#80349E" groupname="Mods" icon="fas fa-bolt" id="4">@"Mods"#g4</GROUPMENTION> will look at this</p></r>'],
            ],
            'post_mentions_group' => [
                ['post_id' => 4, 'mentions_group_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
            ],
            'groups' => [
                [
                    'id' => 10,
                    'name_singular' => 'Hidden',
                    'name_plural' => 'Ninjas',
                    'color' => null,
                    'icon' => 'fas fa-wrench',
                    'is_hidden' => 1
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function render_a_group_mention_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('<p>One of the <span style="background:#80349E" class="GroupMention">@Mods<i class="icon fas fa-bolt"></i></span> will look at this</p>', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsGroups->find(4));
    }

    /**
     * @test
     */
    public function mention_a_group_as_an_admin_user()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
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
    public function mention_multiple_groups_as_an_admin_user()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Admins"#g1 @"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
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
    public function mention_a_virtual_group_as_an_admin_user_does_not_mention()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Members"#g3 @"Guests"#g2',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

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
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
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
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'mentionGroups'],
            ]
        ]);
        
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Mods"#g4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
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
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'mentionGroups'],
            ]
        ]);
        
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Ninjas"#g10',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
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
}
