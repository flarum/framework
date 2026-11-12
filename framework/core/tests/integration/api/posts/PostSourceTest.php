<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Flarum\Discussion\Discussion;
use Flarum\Api\Client;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Post\PostSource;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class PostSourceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 3, 'last_post_number' => 4],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t>web</t>'],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::now()->subHours(2), 'user_id' => 2, 'type' => 'comment', 'content' => '<t>api</t>', 'source' => 'api'],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'created_at' => Carbon::now()->subHour(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t>mcp</t>', 'source' => 'mcp', 'source_name' => 'claude.ai'],
                ['id' => 4, 'discussion_id' => 1, 'number' => 4, 'created_at' => Carbon::now()->subMinutes(30), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '["a","b"]'],
            ],
            ApiKey::class => [
                ['key' => 'mastertoken', 'user_id' => null, 'created_at' => Carbon::now()],
            ],
            AccessToken::class => [
                ['token' => 'developertoken', 'user_id' => 2, 'type' => 'developer', 'created_at' => Carbon::now(), 'last_activity_at' => Carbon::now()],
            ],
        ]);

        $this->setting('show_post_source', '1');
    }

    #[Test]
    public function reply_from_the_forum_has_no_source()
    {
        $post = $this->reply($this->request('POST', '/api/posts', ['authenticatedAs' => 2, 'json' => $this->replyBody()]));

        $this->assertNull($post->source);
        $this->assertNull($post->source_name);
    }

    #[Test]
    public function reply_with_an_api_key_records_api()
    {
        $post = $this->reply(
            $this->request('POST', '/api/posts', ['json' => $this->replyBody()])
                ->withAddedHeader('Authorization', 'Token mastertoken; userId=2')
        );

        $this->assertEquals('api', $post->source);
        $this->assertNull($post->source_name);
    }

    #[Test]
    public function reply_with_a_developer_token_records_api()
    {
        $post = $this->reply(
            $this->request('POST', '/api/posts', ['json' => $this->replyBody()])
                ->withAddedHeader('Authorization', 'Token developertoken')
        );

        $this->assertEquals('api', $post->source);
    }

    #[Test]
    public function first_post_of_a_discussion_records_the_source()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'json' => ['data' => ['type' => 'discussions', 'attributes' => ['title' => 'Via the API', 'content' => 'First post made with a token']]],
            ])->withAddedHeader('Authorization', 'Token developertoken')
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $id = json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertEquals('api', Discussion::query()->findOrFail($id)->firstPost->source);
    }

    #[Test]
    public function source_cannot_be_set_by_the_client()
    {
        $body = $this->replyBody();
        $body['data']['attributes']['source'] = 'mcp';
        $body['data']['attributes']['sourceName'] = 'Claude';

        $response = $this->send($this->request('POST', '/api/posts', ['authenticatedAs' => 2, 'json' => $body]));

        // Read-only fields are refused rather than ignored.
        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(4, Post::query()->count());
    }

    #[Test]
    public function source_is_serialized()
    {
        $response = $this->send($this->request('GET', '/api/posts/3', ['authenticatedAs' => 2]));
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertSame('mcp', $attributes['source']);
        $this->assertSame('claude.ai', $attributes['sourceName']);

        $response = $this->send($this->request('GET', '/api/posts/1'));
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertArrayHasKey('source', $attributes);
        $this->assertNull($attributes['source']);
    }

    #[Test]
    public function source_is_hidden_when_the_setting_is_off()
    {
        $this->setting('show_post_source', '0');

        $response = $this->send($this->request('GET', '/api/posts/3'));
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertArrayNotHasKey('source', $attributes);
        $this->assertArrayNotHasKey('sourceName', $attributes);

        $forum = json_decode((string) $this->send($this->request('GET', '/api'))->getBody(), true);
        $this->assertFalse($forum['data']['attributes']['showPostSource']);
    }

    #[Test]
    public function forum_shows_sources_when_enabled()
    {
        $forum = json_decode((string) $this->send($this->request('GET', '/api'))->getBody(), true);

        $this->assertTrue($forum['data']['attributes']['showPostSource']);
    }

    #[Test]
    public function filters_by_source()
    {
        $this->assertEquals(['3'], $this->listIds(['source' => 'mcp']));
        $this->assertEquals(['2', '3'], $this->listIds(['source' => 'api,mcp']));
        $this->assertEquals(['3'], $this->listIds(['source' => 'MCP']), 'Matching ignores case on every database.');

        Post::query()->whereKey(2)->update(['source' => '0']);
        $this->assertEquals(['2'], $this->listIds(['source' => '0']), 'A key of "0" is a key, not an empty value.');
    }

    #[Test]
    public function negating_several_sources_keeps_only_forum_posts()
    {
        $this->assertEquals(['1'], $this->listIds(['-source' => 'api,mcp']));
    }

    #[Test]
    public function source_cannot_be_changed_on_update()
    {
        $response = $this->send($this->request('PATCH', '/api/posts/1', [
            'authenticatedAs' => 1,
            'json' => ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['source' => 'mcp']]],
        ]));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(Post::query()->findOrFail(1)->source);
    }

    #[Test]
    public function event_posts_have_no_source_fields()
    {
        $response = $this->send($this->request('GET', '/api/posts/4', ['authenticatedAs' => 1]));
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertArrayNotHasKey('source', $attributes);
    }

    #[Test]
    public function internal_api_calls_keep_the_parent_request_source()
    {
        /** @var Client $client */
        $client = $this->app()->getContainer()->make(Client::class);

        $parent = RequestUtil::withPostSource(
            RequestUtil::withActor(new ServerRequest(), User::query()->findOrFail(2)),
            new PostSource('email', 'Email')
        );
        $response = $client->withParentRequest($parent)->withBody($this->replyBody())->post('/posts');

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $post = Post::query()->findOrFail(json_decode((string) $response->getBody(), true)['data']['id']);
        $this->assertEquals('email', $post->source);
        $this->assertEquals('Email', $post->source_name);
    }

    #[Test]
    public function negated_filter_keeps_posts_made_in_the_forum()
    {
        $this->assertEquals(['1', '2'], $this->listIds(['-source' => 'mcp']));
    }

    #[Test]
    public function filter_is_ignored_when_the_setting_is_off()
    {
        $this->setting('show_post_source', '0');

        $this->assertEquals(['1', '2', '3'], $this->listIds(['source' => 'mcp']));
    }

    protected function replyBody(): array
    {
        return [
            'data' => [
                'type' => 'posts',
                'attributes' => ['content' => 'A reply long enough to pass validation'],
                'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
            ],
        ];
    }

    protected function reply(ServerRequestInterface $request): Post
    {
        $response = $this->send($request);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        return Post::query()->findOrFail(json_decode((string) $response->getBody(), true)['data']['id']);
    }

    /**
     * @return list<string>
     */
    protected function listIds(array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => [...$filter, 'type' => 'comment'], 'sort' => 'createdAt'])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $ids = array_column(json_decode((string) $response->getBody(), true)['data'], 'id');
        sort($ids);

        return array_values($ids);
    }
}
