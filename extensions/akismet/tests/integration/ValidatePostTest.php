<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\integration;

use Carbon\Carbon;
use Flarum\Akismet\Tests\fixtures\FakeAkismetProvider;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;

class ValidatePostTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval', 'flarum-akismet');

        $this->extend(
            (new Extend\ServiceProvider())->register(FakeAkismetProvider::class)
        );

        FakeAkismetProvider::reset();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Existing discussion', 'created_at' => Carbon::parse('2024-01-01'), 'last_posted_at' => Carbon::parse('2024-01-01'), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2024-01-01'), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post</p></t>'],
            ],
        ]);
    }

    private function reply(string $content = 'A perfectly normal reply message.'): \Psr\Http\Message\ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => $content],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => 1]]],
                    ],
                ],
            ])
        );
    }

    private function lastCheckParams(): array
    {
        $requests = FakeAkismetProvider::$history;
        $this->assertNotEmpty($requests, 'Expected a comment-check request to have been sent.');

        parse_str((string) $requests[count($requests) - 1]['request']->getBody(), $params);

        return $params;
    }

    #[Test]
    public function a_spam_reply_is_unapproved_and_flagged()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());

        $post = Post::query()->orderByDesc('id')->first();

        $this->assertTrue((bool) $post->is_spam);
        $this->assertFalse((bool) $post->is_approved);
        $this->assertSame(1, $post->flags()->where('type', 'akismet')->count());
    }

    #[Test]
    public function a_ham_reply_posts_normally()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'false')]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());

        $post = Post::query()->orderByDesc('id')->first();

        $this->assertFalse((bool) $post->is_spam);
        $this->assertTrue((bool) $post->is_approved);
        $this->assertSame(0, $post->flags()->count());
    }

    #[Test]
    public function the_check_carries_the_accuracy_data_points()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'false')]);

        $this->reply('The reply body.');

        $params = $this->lastCheckParams();

        $this->assertSame('reply', $params['comment_type']);
        $this->assertStringContainsString('The reply body.', $params['comment_content']);
        $this->assertSame('UTF-8', $params['blog_charset']);
        $this->assertArrayHasKey('blog_lang', $params);
        $this->assertStringContainsString('/d/1', $params['permalink']);
        $this->assertSame('normal', $params['comment_author']);
    }

    #[Test]
    public function a_spam_first_post_folds_the_title_into_the_content()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Cheap watches here',
                            'content' => 'Buy now, best prices.',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $params = $this->lastCheckParams();

        $this->assertSame('forum-post', $params['comment_type']);
        $this->assertStringContainsString('Cheap watches here', $params['comment_content']);
        $this->assertStringContainsString('Buy now, best prices.', $params['comment_content']);

        $discussion = Discussion::query()->orderByDesc('id')->first();

        $this->assertFalse((bool) $discussion->is_approved);
    }

    #[Test]
    public function an_unreachable_akismet_fails_open_and_the_post_saves()
    {
        FakeAkismetProvider::reset([
            new ConnectException('Connection refused', new Request('POST', 'comment-check')),
        ]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());

        $post = Post::query()->orderByDesc('id')->first();

        $this->assertFalse((bool) $post->is_spam);
        $this->assertTrue((bool) $post->is_approved);
    }

    #[Test]
    public function an_invalid_key_response_fails_open_instead_of_passing_silently_as_ham()
    {
        FakeAkismetProvider::reset([
            new Response(200, ['X-akismet-debug-help' => 'Missing required field: api_key.'], 'invalid'),
        ]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue((bool) Post::query()->orderByDesc('id')->first()->is_approved);
    }

    #[Test]
    public function editing_spam_into_an_approved_post_is_rechecked_and_flagged()
    {
        // The original check passed; spam arrives via the edit.
        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => ['attributes' => ['content' => 'Actually, cheap watches here.']],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $params = $this->lastCheckParams();

        $this->assertSame('edit', $params['recheck_reason']);

        $post = Post::query()->find(1);

        $this->assertTrue((bool) $post->is_spam);
        $this->assertFalse((bool) $post->is_approved);
        $this->assertSame(1, $post->flags()->where('type', 'akismet')->count());
    }

    #[Test]
    public function editing_without_changing_content_is_not_rechecked()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => ['attributes' => ['content' => 'first post']],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty(FakeAkismetProvider::$history, 'Unchanged content must not be sent to Akismet.');
    }

    #[Test]
    public function blatant_spam_is_deleted_outright_when_the_setting_is_on()
    {
        $this->setting('flarum-akismet.delete_blatant_spam', '1');

        FakeAkismetProvider::reset([
            new Response(200, ['X-akismet-pro-tip' => 'discard'], 'true'),
            // The Hidden event then submits spam feedback; body is ignored.
            new Response(200, [], 'Thanks for making the web a better place.'),
        ]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());

        $post = Post::query()->orderByDesc('id')->first();

        $this->assertNotNull($post->hidden_at);
        $this->assertSame(0, $post->flags()->count());
    }

    #[Test]
    public function users_with_the_bypass_permission_are_not_checked()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'bypassAkismet', 'group_id' => 3],
            ],
        ]);

        FakeAkismetProvider::reset([new Response(200, [], 'true')]);

        $response = $this->reply();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEmpty(FakeAkismetProvider::$history, 'Bypassing users must not be sent to Akismet.');
    }
}
