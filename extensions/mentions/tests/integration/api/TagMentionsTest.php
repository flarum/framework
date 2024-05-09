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
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class TagMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'toby', 'email' => 'toby@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><TAGMENTION id="1" slug="test_old_slug" tagname="TestOldName">#test_old_slug</TAGMENTION></r>'],
                ['id' => 7, 'number' => 5, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2021, 'type' => 'comment', 'content' => '<r><TAGMENTION id="3" slug="support" tagname="Support">#deleted_relation</TAGMENTION></r>'],
                ['id' => 8, 'number' => 6, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="2020" slug="i_am_a_deleted_tag" tagname="i_am_a_deleted_tag">#i_am_a_deleted_tag</TAGMENTION></r>'],
                ['id' => 10, 'number' => 11, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="5" slug="laravel">#laravel</TAGMENTION></r>'],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Test', 'slug' => 'test', 'is_restricted' => 0],
                ['id' => 2, 'name' => 'Flarum', 'slug' => 'flarum', 'is_restricted' => 0],
                ['id' => 3, 'name' => 'Support', 'slug' => 'support', 'is_restricted' => 0],
                ['id' => 4, 'name' => 'Dev', 'slug' => 'dev', 'is_restricted' => 1],
                ['id' => 5, 'name' => 'Laravel "#t6 Tag', 'slug' => 'laravel', 'is_restricted' => 0],
                ['id' => 6, 'name' => 'Tatakai', 'slug' => '戦い', 'is_restricted' => 0],
            ],
            'post_mentions_tag' => [
                ['post_id' => 4, 'mentions_tag_id' => 1],
                ['post_id' => 5, 'mentions_tag_id' => 2],
                ['post_id' => 6, 'mentions_tag_id' => 3],
                ['post_id' => 10, 'mentions_tag_id' => 4],
                ['post_id' => 10, 'mentions_tag_id' => 5],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
            ],
        ]);
    }

    /** @test */
    public function mentioning_a_valid_tag_with_valid_format_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#flarum',
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

        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(2));
    }

    /** @test */
    public function mentioning_a_valid_tag_using_cjk_slug_with_valid_format_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#戦い',
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

        $this->assertStringContainsString('Tatakai', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(6));
    }

    /** @test */
    public function mentioning_an_invalid_tag_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#franzofflarum',
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

        $this->assertEquals('#franzofflarum', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function mentioning_a_tag_when_tags_disabled_does_not_cause_errors()
    {
        $this->extensions = ['flarum-mentions'];

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#test',
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

        $this->assertEquals('#test', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function mentioning_a_restricted_tag_doesnt_work_without_privileges()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#dev',
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

        $this->assertEquals('#dev', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function mentioning_a_restricted_tag_works_with_privileges()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#dev',
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

        $this->assertEquals('#dev', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function mentioning_multiple_tags_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#test #flarum #support #laravel #franzofflarum',
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

        $this->assertStringContainsString('Test', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('Flarum', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('#test #flarum #support #laravel #franzofflarum', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertCount(4, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function tag_mentions_render_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Test', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function tag_mentions_dont_cause_errors_when_tags_disabled()
    {
        $this->extensions = ['flarum-mentions'];

        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function tag_mentions_unparse_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('#test', $response['data']['attributes']['content']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function deleted_tag_mentions_unparse_and_render_as_expected()
    {
        // No reason to hide a deleted tag's name.
        $deleted_text = 'i_am_a_deleted_tag';

        $response = $this->send(
            $this->request('GET', '/api/posts/8', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString($deleted_text, $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString("#$deleted_text", $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function deleted_tag_mentions_relation_unparse_and_render_as_expected()
    {
        // No reason to hide a deleted tag's name.
        $deleted_text = 'deleted_relation';

        $response = $this->send(
            $this->request('GET', '/api/posts/7', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Support', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString("#$deleted_text", $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function editing_a_post_that_has_a_tag_mention_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/10', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '#laravel',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Laravel "#t6 Tag', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('#laravel', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(5));
    }
}
