<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PostSourceTest extends McpTestCase
{
    use IssuesOAuthTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Existing', 'slug' => 'existing', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>', 'created_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    #[Test]
    public function reply_with_a_developer_token_is_via_mcp(): void
    {
        $post = $this->reply($this->bearer($this->developerToken(2)));

        $this->assertSame('mcp', $post->created_via);
        $this->assertSame('MCP', $post->created_via_name);
    }

    #[Test]
    public function first_post_of_a_discussion_is_via_mcp(): void
    {
        $created = $this->toolOutput($this->callTool('start_discussion', ['title' => 'Started over MCP', 'content' => 'Hello'], $this->bearer($this->developerToken(2))));

        $this->assertSame('mcp', Discussion::query()->findOrFail($created['id'])->firstPost->created_via);
    }

    #[Test]
    public function a_public_client_is_not_named_even_when_an_admin_created_it(): void
    {
        $this->setUpOAuth();
        // As a member can: approve their own authorization, read the code and redeem it without a secret.
        $jwt = $this->issueOAuthToken('mcp:read mcp:write');

        $post = $this->reply($this->bearer($jwt));

        $this->assertSame('mcp', $post->created_via);
        $this->assertSame('MCP', $post->created_via_name);
    }

    #[Test]
    #[DataProvider('clients')]
    public function client_is_named_only_when_it_proves_who_it_is(string $source, string $id, array $redirectUris, bool $confidential, string $expected): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read mcp:write');

        // Move the token to another client; the token stays valid.
        $this->database()->table('oauth_provider_clients')->insert([
            'id' => $id,
            'name' => 'Claude',
            'secret' => $confidential ? 'hashed-secret' : null,
            'redirect_uris' => json_encode($redirectUris),
            'scopes' => null,
            'confidential' => (int) $confidential,
            'revoked' => 0,
            'source' => $source,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);
        $this->database()->table('oauth_provider_access_tokens')->update(['client_id' => $id]);

        $post = $this->reply($this->bearer($jwt));

        $this->assertSame('mcp', $post->created_via);
        $this->assertSame($expected, $post->created_via_name);
    }

    public static function clients(): array
    {
        return [
            // Public clients need no secret, so the member could have redeemed the code themselves.
            'registered, public host' => ['dynamic', 'registered', ['https://claude.ai/api/mcp/auth_callback'], false, 'MCP'],
            'registered, loopback' => ['dynamic', 'registered', ['http://localhost:33418/callback'], false, 'MCP'],
            'metadata document' => ['metadata_document', 'https://app.example/oauth/client.json', ['https://app.example/cb'], false, 'MCP'],
            'admin, public, https' => ['admin', 'admin-public', ['https://assistant.example/cb'], false, 'MCP'],
            'admin, confidential' => ['admin', 'admin-confidential', ['https://assistant.example/cb'], true, 'Claude'],
            'admin, confidential, loopback' => ['admin', 'admin-confidential-loopback', ['http://localhost:33418/cb'], true, 'Claude'],
        ];
    }

    #[Test]
    public function reads_do_not_look_up_the_client(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read');

        $this->database()->enableQueryLog();
        $this->toolOutput($this->callTool('whoami', [], $this->bearer($jwt)));

        $this->assertEmpty(array_filter(
            $this->database()->getQueryLog(),
            fn (array $query) => str_contains($query['query'], 'oauth_provider_clients') && ! str_contains($query['query'], 'exists')
        ));
    }

    protected function reply(array $auth): Post
    {
        $reply = $this->toolOutput($this->callTool('reply_to_discussion', ['discussionId' => '1', 'content' => 'A reply'], $auth));

        return Post::query()->findOrFail($reply['id']);
    }
}
