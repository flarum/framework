<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OidcDiscoveryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-oauth-provider');
    }

    #[Test]
    public function discovery_document_has_required_fields(): void
    {
        $response = $this->send(
            $this->request('GET', '/.well-known/openid-configuration')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));

        $body = json_decode((string) $response->getBody(), true);

        // OIDC Discovery 1.0 §3 required fields.
        $this->assertArrayHasKey('issuer', $body);
        $this->assertArrayHasKey('authorization_endpoint', $body);
        $this->assertArrayHasKey('token_endpoint', $body);
        $this->assertArrayHasKey('jwks_uri', $body);
        $this->assertArrayHasKey('response_types_supported', $body);
        $this->assertArrayHasKey('subject_types_supported', $body);
        $this->assertArrayHasKey('id_token_signing_alg_values_supported', $body);

        $this->assertContains('code', $body['response_types_supported']);
        $this->assertContains('RS256', $body['id_token_signing_alg_values_supported']);
    }

    #[Test]
    public function discovery_endpoints_all_absolute_urls(): void
    {
        $response = $this->send(
            $this->request('GET', '/.well-known/openid-configuration')
                ->withAttribute('bypassCsrfToken', true)
        );

        $body = json_decode((string) $response->getBody(), true);

        foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint', 'jwks_uri'] as $field) {
            $this->assertMatchesRegularExpression('#^https?://#', $body[$field], "$field is not absolute: {$body[$field]}");
        }
    }

    #[Test]
    public function discovery_advertises_registered_scopes(): void
    {
        $response = $this->send(
            $this->request('GET', '/.well-known/openid-configuration')
                ->withAttribute('bypassCsrfToken', true)
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertContains('openid', $body['scopes_supported']);
        $this->assertContains('profile', $body['scopes_supported']);
        $this->assertContains('email', $body['scopes_supported']);
    }

    #[Test]
    public function jwks_returns_valid_rsa_key(): void
    {
        $response = $this->send(
            $this->request('GET', '/.well-known/jwks.json')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('keys', $body);
        $this->assertCount(1, $body['keys']);

        $key = $body['keys'][0];
        $this->assertEquals('RSA', $key['kty']);
        $this->assertEquals('sig', $key['use']);
        $this->assertEquals('RS256', $key['alg']);
        $this->assertArrayHasKey('n', $key);
        $this->assertArrayHasKey('e', $key);
        $this->assertArrayHasKey('kid', $key);

        // `n` and `e` are base64url-encoded — no +, /, or = allowed.
        $this->assertDoesNotMatchRegularExpression('#[+/=]#', $key['n']);
        $this->assertDoesNotMatchRegularExpression('#[+/=]#', $key['e']);
    }
}
