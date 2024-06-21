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
use Flarum\Extend;
use Flarum\Formatter\Formatter;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class PostMentionsTest extends TestCase
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
                ['id' => 5, 'username' => 'bad_user', 'email' => 'bad_user@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
                ['id' => 50, 'title' => __CLASS__, 'is_private' => true, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="TobyFlarum___" id="5" number="2" discussionid="2" username="toby">@tobyuuu#5</POSTMENTION></r>'],
                ['id' => 5, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="potato" id="4" number="3" discussionid="2" username="potato">@potato#4</POSTMENTION></r>'],
                ['id' => 6, 'number' => 4, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="i_am_a_deleted_user" id="7" number="5" discussionid="2" username="i_am_a_deleted_user">@"i_am_a_deleted_user"#p7</POSTMENTION></r>'],
                ['id' => 7, 'number' => 5, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2021, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="POTATO$" id="2010" number="7" discussionid="2">@"POTATO$"#2010</POSTMENTION></r>'],
                ['id' => 8, 'number' => 6, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="i_am_a_deleted_user" id="2020" number="8" discussionid="2" username="i_am_a_deleted_user">@"i_am_a_deleted_user"#p2020</POSTMENTION></r>'],
                ['id' => 9, 'number' => 10, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 5, 'type' => 'comment', 'content' => '<r><p>I am bad</p></r>'],
                ['id' => 10, 'number' => 11, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="Bad &quot;#p6 User" id="9" number="10" discussionid="2">@"Bad "#p6 User"#p9</POSTMENTION></r>'],
                ['id' => 11, 'number' => 12, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => null, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="Bad &quot;#p6 User" id="9" number="10" discussionid="2">@"Bad "#p6 User"#p9</POSTMENTION></r>'],
                ['id' => 12, 'number' => 13, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="deleted_user" id="11" number="12" discussionid="2">@"acme"#p11</POSTMENTION></r>'],

                // Restricted access
                ['id' => 50, 'number' => 1, 'discussion_id' => 50, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r>no</r>'],
            ],
            'post_mentions_post' => [
                ['post_id' => 4, 'mentions_post_id' => 5],
                ['post_id' => 5, 'mentions_post_id' => 4],
                ['post_id' => 6, 'mentions_post_id' => 7],
                ['post_id' => 10, 'mentions_post_id' => 9],
            ],
        ]);

        $this->setting('display_name_driver', 'custom_display_name_driver');

        $this->extend(
            (new Extend\User)
                ->displayNameDriver('custom_display_name_driver', CustomOtherDisplayNameDriver::class)
        );
    }

    /**
     * @test
     */
    public function mentioning_a_valid_post_with_old_format_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@potato#4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        $response = json_decode($body, true);

        $this->assertStringNotContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@potato#4', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(4));
    }

    /**
     * @test
     */
    public function mentioning_a_valid_post_with_new_format_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"POTATO$"#p4',
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

        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"POTATO$"#p4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(4));
    }

    /**
     * @test
     */
    public function cannot_mention_a_post_without_access()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"potato"#p50',
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

        $this->assertStringContainsString('potato', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"potato"#p50', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(50));
    }

    /**
     * @test
     */
    public function mentioning_a_valid_post_with_new_format_with_smart_quotes_works_and_falls_back_to_normal_quotes()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@“POTATO$”#p4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        $response = json_decode($body, true);

        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"POTATO$"#p4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(4));
    }

    /**
     * @test
     */
    public function mentioning_an_invalid_post_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"franzofflarum"#p215',
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

        $this->assertStringNotContainsString('FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"franzofflarum"#p215', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function mentioning_multiple_posts_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"TOBY$"#p5 @"flarum"#2015 @"franzofflarum"#220 @"POTATO$"#3 @"POTATO$"#p4',
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

        $this->assertStringContainsString('TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"TOBY$"#p5 @"flarum"#2015 @"franzofflarum"#220 @"POTATO$"#3 @"POTATO$"#p4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(2, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function post_mentions_render_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function post_mentions_unparse_with_fresh_data()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('@"TOBY$"#p5', $response['data']['attributes']['content']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function deleted_post_mentions_s_user_unparse_and_render_without_user_data()
    {
        $deleted_text = $this->app()->getContainer()->make('translator')->trans('core.lib.username.deleted_text');

        $response = $this->send(
            $this->request('GET', '/api/posts/6', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString($deleted_text, $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"'.$deleted_text.'"#p7', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('i_am_a_deleted_user', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('i_am_a_deleted_user', $response['data']['attributes']['content']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function deleted_post_mentions_unparse_and_render_without_user_data()
    {
        $deleted_text = $this->app()->getContainer()->make('translator')->trans('flarum-mentions.forum.post_mention.deleted_text');

        $response = $this->send(
            $this->request('GET', '/api/posts/7', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString($deleted_text, $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"'.$deleted_text.'"#p2010', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('POTATO$', $response['data']['attributes']['content']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function deleted_post_mentions_and_deleted_user_unparse_and_render_without_user_data()
    {
        $deleted_text = $this->app()->getContainer()->make('translator')->trans('flarum-mentions.forum.post_mention.deleted_text');

        $response = $this->send(
            $this->request('GET', '/api/posts/8', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString($deleted_text, $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"'.$deleted_text.'"#p2020', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('POTATO$', $response['data']['attributes']['content']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function post_mentions_with_unremoved_bad_string_from_display_names_doesnt_work()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Bad "#p6 User"#p9',
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

        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"POTATO$"#p6 User"#p9', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(6));
    }

    /**
     * @test
     */
    public function post_mentions_unparsing_removes_bad_display_name_string()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/10', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"Bad _ User"#p9', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(9));
    }

    /**
     * @test
     */
    public function post_mentions_with_removed_bad_string_from_display_names_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Bad _ User"#p9',
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

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Bad _ User"#p9', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(9));
    }

    /**
     * @test
     */
    public function editing_a_post_that_has_a_mention_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/10', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Bad _ User"#p9',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Bad _ User"#p9', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(9));
    }

    /**
     * @test
     */
    public function editing_a_post_with_deleted_author_that_has_a_mention_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/11', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"Bad _ User"#p9',
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $response = json_decode($body, true);

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Bad _ User"#p9', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(9));
    }

    /**
     * @test
     */
    public function editing_a_post_with_a_mention_of_a_post_with_deleted_author_works()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/12', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'posts',
                        'attributes' => [
                            'content' => '@"acme"#p11',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('[deleted]', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"[deleted]"#p11', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(11));
    }

    /**
     * @test
     */
    public function rendering_post_mention_with_a_post_context_works()
    {
        /** @var Formatter $formatter */
        $formatter = $this->app()->getContainer()->make(Formatter::class);

        $post = Post::find(4);
        $user = User::find(1);

        $xml = $formatter->parse($post->content, $post, $user);
        $renderedHtml = $formatter->render($xml, $post);

        $this->assertStringContainsString('TOBY$', $renderedHtml);
    }

    /**
     * @test
     */
    public function rendering_post_mention_without_a_context_works()
    {
        /** @var Formatter $formatter */
        $formatter = $this->app()->getContainer()->make(Formatter::class);

        $post = Post::find(4);
        $user = User::find(1);

        $xml = $formatter->parse($post->content, null, $user);
        $renderedHtml = $formatter->render($xml);

        $this->assertStringContainsString('TOBY$', $renderedHtml);
    }
}

class CustomOtherDisplayNameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        if ($user->username === 'bad_user') {
            return 'Bad "#p6 User';
        }

        return strtoupper($user->username).'$';
    }
}
