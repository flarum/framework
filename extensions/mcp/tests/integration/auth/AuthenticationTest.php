<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\auth;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Tests\integration\IssuesOAuthTokens;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\OAuthProvider\Extend\Resource as OAuthResource;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationTest extends McpTestCase
{
    use IssuesOAuthTokens;

    public const string METADATA = 'resource_metadata="http://localhost/.well-known/oauth-protected-resource/mcp"';

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    protected function allowGuests(): void
    {
        $this->prepareDatabase([
            'group_permission' => [['group_id' => Group::GUEST_ID, 'permission' => 'mcp.use']],
        ]);
    }

    #[Test]
    public function guest_without_permission_is_challenged(): void
    {
        $response = $this->modern('tools/list');

        $this->assertSame(401, $response->getStatusCode());

        $challenge = $response->getHeaderLine('WWW-Authenticate');
        $this->assertStringStartsWith('Bearer ', $challenge);
        $this->assertStringContainsString(self::METADATA, $challenge);
        // A read asks for read access only; write is asked for when a write tool is called.
        $this->assertStringContainsString('scope="mcp:read"', $challenge);
        $this->assertStringNotContainsString('error=', $challenge);

        $write = $this->callTool('start_discussion', ['title' => 'Hello', 'content' => 'World']);
        $this->assertSame(401, $write->getStatusCode());
        $this->assertStringContainsString('scope="mcp:read mcp:write"', $write->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function guest_with_permission_lists_tools_and_calls_a_read_tool(): void
    {
        $this->allowGuests();

        $tools = $this->rpcResult($this->modern('tools/list'))['tools'];
        $byName = array_column($tools, null, 'name');

        $this->assertArrayHasKey('search_discussions', $byName);
        $this->assertArrayNotHasKey('list_notifications', $byName);

        // Write tools stay listed so the model can ask the user to sign in.
        $this->assertStringStartsWith('Requires signing in.', $byName['start_discussion']['description']);
        $this->assertFalse($byName['start_discussion']['annotations']['readOnlyHint']);
        $this->assertTrue($byName['search_discussions']['annotations']['readOnlyHint']);

        $whoami = $this->toolOutput($this->callTool('whoami'));
        $this->assertTrue($whoami['guest']);
        $this->assertSame(['mcp:read'], $whoami['scopes']);
    }

    #[Test]
    public function guest_calling_a_write_tool_is_challenged_for_write_scope(): void
    {
        $this->allowGuests();

        $response = $this->callTool('start_discussion', ['title' => 'Hello', 'content' => 'World']);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('scope="mcp:read mcp:write"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringContainsString(self::METADATA, $response->getHeaderLine('WWW-Authenticate'));
        $this->assertSame(0, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function a_batch_with_a_write_call_is_checked_per_message(): void
    {
        $this->allowGuests();

        $response = $this->send($this->mcpRequest([
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'reply_to_discussion', 'arguments' => ['discussionId' => '1', 'content' => 'x']]],
        ], ['MCP-Protocol-Version' => self::HANDSHAKE]));

        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function developer_token_acts_as_its_user_and_can_write(): void
    {
        $token = $this->developerToken(2);

        $whoami = $this->toolOutput($this->callTool('whoami', [], $this->bearer($token)));
        $this->assertFalse($whoami['guest']);
        $this->assertSame('normal', $whoami['username']);
        $this->assertSame(['mcp:read', 'mcp:write'], $whoami['scopes']);

        $created = $this->toolOutput($this->callTool('start_discussion', ['title' => 'From MCP', 'content' => 'Hello there'], $this->bearer($token)));

        $this->assertSame('From MCP', $created['title']);
        $this->assertSame(2, (int) $this->database()->table('discussions')->where('id', $created['id'])->value('user_id'));
    }

    #[Test]
    public function session_token_is_refused(): void
    {
        $response = $this->modern('tools/list', [], $this->bearer($this->sessionToken(2)));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringContainsString(self::METADATA, $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function token_header_api_key_is_refused(): void
    {
        $this->database()->table('api_keys')->insert([
            'key' => 'master-key-for-tests',
            'user_id' => 1,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);

        $response = $this->modern('tools/list', [], ['Authorization' => 'Token master-key-for-tests; userId=1']);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function an_unknown_bearer_token_is_refused(): void
    {
        $response = $this->modern('tools/list', [], $this->bearer('not-a-real-token'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringContainsString('scope="mcp:read"', $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function user_without_permission_is_forbidden(): void
    {
        $token = $this->developerToken(2);
        $this->database()->table('group_permission')->where('permission', 'mcp.use')->delete();

        $response = $this->modern('tools/list', [], $this->bearer($token));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('forbidden', $this->json($response)['error']);
        $this->assertArrayHasKey('error_description', $this->json($response));
        $this->assertArrayNotHasKey('jsonrpc', $this->json($response));
    }

    #[Test]
    public function a_cookie_session_without_authorization_is_a_guest(): void
    {
        $this->allowGuests();

        $request = $this->mcpRequest([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'whoami',
                'arguments' => new \stdClass(),
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => self::MODERN,
                    'io.modelcontextprotocol/clientCapabilities' => new \stdClass(),
                ],
            ],
        ], ['MCP-Protocol-Version' => self::MODERN, 'Mcp-Method' => 'tools/call', 'Mcp-Name' => 'whoami']);

        // Logged in through the remember cookie, as a browser would be.
        $loggedIn = $this->request('GET', '/', ['authenticatedAs' => 2]);
        $request = $request->withCookieParams($loggedIn->getCookieParams());

        $this->assertTrue($this->toolOutput($this->send($request))['guest']);
    }

    #[Test]
    public function oauth_token_for_this_resource_acts_as_its_user(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read mcp:write');

        $whoami = $this->toolOutput($this->callTool('whoami', [], $this->bearer($jwt)));
        $this->assertSame('normal', $whoami['username']);
        $this->assertSame(['mcp:read', 'mcp:write'], $whoami['scopes']);

        $created = $this->toolOutput($this->callTool('start_discussion', ['title' => 'Via OAuth', 'content' => 'Hello'], $this->bearer($jwt)));
        $this->assertSame(2, (int) $this->database()->table('discussions')->where('id', $created['id'])->value('user_id'));
    }

    #[Test]
    public function oauth_token_for_another_resource_is_refused(): void
    {
        $this->extend(new OAuthResource(fn (UrlGenerator $url) => $url->to('forum')->base().'/elsewhere', ['mcp:read']));
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read', 'http://localhost/elsewhere');
        $this->assertContains('http://localhost/elsewhere', (array) $this->jwtClaims($jwt)['aud']);

        $response = $this->modern('tools/list', [], $this->bearer($jwt));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringContainsString(self::METADATA, $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function revoked_oauth_token_is_refused(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read');

        $this->assertSame(200, $this->modern('tools/list', [], $this->bearer($jwt))->getStatusCode());

        $this->database()->table('oauth_provider_access_tokens')->where('id', $this->jwtClaims($jwt)['jti'])->update(['revoked' => true]);

        $response = $this->modern('tools/list', [], $this->bearer($jwt));
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function an_oauth_token_records_the_address_it_was_used_from(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read');
        $this->remoteAddress = '203.0.113.7';

        $this->assertSame(200, $this->modern('tools/list', [], $this->bearer($jwt))->getStatusCode());

        $this->assertSame(
            '203.0.113.7',
            $this->database()->table('oauth_provider_access_tokens')->where('id', $this->jwtClaims($jwt)['jti'])->value('last_ip_address')
        );
    }

    #[Test]
    public function a_forged_jwt_is_refused(): void
    {
        $response = $this->modern('tools/list', [], $this->bearer('eyJhbGciOiJub25lIn0.eyJhdWQiOiJodHRwOi8vbG9jYWxob3N0L21jcCJ9.'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function read_only_oauth_token_calling_a_write_tool_lacks_scope(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:read');

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $this->bearer($jwt)))['tools'], null, 'name');
        $this->assertStringStartsWith('Requires write access.', $tools['start_discussion']['description']);

        $response = $this->callTool('start_discussion', ['title' => 'Hello', 'content' => 'World'], $this->bearer($jwt));

        $this->assertSame(403, $response->getStatusCode());
        $challenge = $response->getHeaderLine('WWW-Authenticate');
        $this->assertStringContainsString('error="insufficient_scope"', $challenge);
        $this->assertStringContainsString('scope="mcp:read mcp:write"', $challenge);
        $this->assertStringContainsString(self::METADATA, $challenge);
        $this->assertSame(0, $this->database()->table('discussions')->count());
    }

    #[Test]
    public function oauth_token_without_an_mcp_scope_is_refused(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('openid');
        $this->assertContains('http://localhost/mcp', (array) $this->jwtClaims($jwt)['aud']);

        foreach (['tools/list', 'server/discover'] as $method) {
            $response = $this->modern($method, [], $this->bearer($jwt));

            $this->assertSame(403, $response->getStatusCode(), $method);
            $challenge = $response->getHeaderLine('WWW-Authenticate');
            $this->assertStringContainsString('error="insufficient_scope"', $challenge);
            $this->assertStringContainsString('scope="mcp:read"', $challenge);
            $this->assertStringContainsString(self::METADATA, $challenge);
        }

        $response = $this->callTool('whoami', [], $this->bearer($jwt));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertArrayNotHasKey('result', $this->json($response));
    }

    #[Test]
    public function write_scope_implies_read_scope(): void
    {
        $this->setUpOAuth();
        $jwt = $this->issueOAuthToken('mcp:write');

        $whoami = $this->toolOutput($this->callTool('whoami', [], $this->bearer($jwt)));
        $this->assertSame(['mcp:read', 'mcp:write'], $whoami['scopes']);
    }
}
