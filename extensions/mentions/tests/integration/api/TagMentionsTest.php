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
use Flarum\Post\CommentPost;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class TagMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-mentions');

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
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><TAGMENTION id="1" slug="test" tagname="Test">@"Test old name"#t1</TAGMENTION></r>'],
                ['id' => 5, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="2" slug="flarum" tagname="Flarum">@"Flarum"#t2</TAGMENTION></r>'],
                ['id' => 6, 'number' => 4, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><TAGMENTION id="3" slug="support" tagname="Support">@"Support"#t3</TAGMENTION></r>'],
                ['id' => 7, 'number' => 5, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2021, 'type' => 'comment', 'content' => '<r><TAGMENTION id="3" slug="support" tagname="Support">@"DeletedMentionFromDatabase"#t3</TAGMENTION></r>'],
                ['id' => 8, 'number' => 6, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="2020" slug="i_am_a_deleted_tag" tagname="i_am_a_deleted_tag">@"i_am_a_deleted_tag"#t2020</TAGMENTION></r>'],
                ['id' => 9, 'number' => 10, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 5, 'type' => 'comment', 'content' => '<r><p>I am bad</p></r>'],
                ['id' => 10, 'number' => 11, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="5" slug="laravel">@"Laravel "#t6 Tag"#t5</TAGMENTION></r>'],
                ['id' => 11, 'number' => 12, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 40, 'type' => 'comment', 'content' => '<r><TAGMENTION id="4" slug="">@"Bad "#t4 User"#t4</TAGMENTION></r>'],
                ['id' => 12, 'number' => 13, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><TAGMENTION id="2" slug="">@"acme"#t2</TAGMENTION></r>'],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Test', 'slug' => 'test', 'is_restricted' => 0],
                ['id' => 2, 'name' => 'Flarum', 'slug' => 'flarum', 'is_restricted' => 0],
                ['id' => 3, 'name' => 'Support', 'slug' => 'support', 'is_restricted' => 0],
                ['id' => 4, 'name' => 'Dev', 'slug' => 'dev', 'is_restricted' => 1],
                ['id' => 5, 'name' => 'Laravel "#t6 Tag', 'slug' => 'laravel', 'is_restricted' => 0],
            ],
            'post_mentions_tag' => [
                ['post_id' => 4, 'mentions_tag_id' => 1],
                ['post_id' => 5, 'mentions_tag_id' => 2],
                ['post_id' => 6, 'mentions_tag_id' => 3],
                ['post_id' => 10, 'mentions_tag_id' => 4],
                ['post_id' => 10, 'mentions_tag_id' => 5],
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
                        'attributes' => [
                            'content' => '@"Flarum"#t2',
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

        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(2));
    }

    /** @test */
    public function mentioning_a_valid_tag_with_valid_format_with_smart_quotes_works_and_falls_back_to_normal_quotes()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@“Test”#t1',
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

        $this->assertStringContainsString('Test', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Test"#t1', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(1));
    }

    /** @test */
    public function mentioning_an_invalid_tag_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"franzofflarum"#t215',
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

        $this->assertEquals('@"franzofflarum"#t215', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function mentioning_multiple_tags_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Test"#t1 @"flarum"#t2 @"Support"#t3 @"Laravel"#t4 @"franzofflarum"#t215',
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

        $this->assertStringContainsString('Test', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('Flarum', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Test"#t1 @"Flarum"#t2 @"Support"#t3 @"Dev"#t4 @"franzofflarum"#t215', $response['data']['attributes']['content']);
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
    public function tag_mentions_unparse_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@"Test"#t1', $response['data']['attributes']['content']);
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
        $this->assertStringContainsString('@"'.$deleted_text.'"#t2020', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function deleted_tag_mentions_relation_unparse_and_render_as_expected()
    {
        // No reason to hide a deleted tag's name.
        $deleted_text = 'Support';

        $response = $this->send(
            $this->request('GET', '/api/posts/7', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString($deleted_text, $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"'.$deleted_text.'"#t3', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('TagMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsTags);
    }

    /** @test */
    public function tag_mentions_with_unremoved_bad_string_from_name_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Laravel "#t6 ss"#t3',
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

        $this->assertStringContainsString('Laravel', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Laravel "#t6 ss"#t3', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsTags->find(3));
    }

    /** @test */
    public function tag_mentions_unparsing_removes_bad_name_string()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/10', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Laravel "#t6 Tag', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Laravel _ Tag"#t5', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsTags->find(6));
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(5));
    }

    /** @test */
    public function tag_mentions_with_removed_bad_string_from_name_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Laravel _ Tag"#t5',
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

        $this->assertStringContainsString('Laravel "#t6 Tag', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Laravel _ Tag"#t5', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(5));
    }

    /** @test */
    public function editing_a_post_that_has_a_tag_mention_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/10', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Laravel _ Tag"#t5',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Laravel "#t6 Tag', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Laravel _ Tag"#t5', $response['data']['attributes']['content']);
        $this->assertStringContainsString('TagMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsTags->find(5));
    }
}
