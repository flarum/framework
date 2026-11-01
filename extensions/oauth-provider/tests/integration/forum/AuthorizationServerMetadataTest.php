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

class AuthorizationServerMetadataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');
    }

    /**
     * @return array<string, mixed>
     */
    protected function document(string $path): array
    {
        $response = $this->send($this->request('GET', $path));

        $this->assertSame(200, $response->getStatusCode());

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function rfc8414_document_describes_the_server(): void
    {
        $body = $this->document('/.well-known/oauth-authorization-server');

        $this->assertSame('http://localhost', $body['issuer']);
        $this->assertSame('http://localhost/oauth/authorize', $body['authorization_endpoint']);
        $this->assertSame('http://localhost/oauth/token', $body['token_endpoint']);
        $this->assertSame('http://localhost/oauth/revoke', $body['revocation_endpoint']);
        $this->assertSame('http://localhost/oauth/register', $body['registration_endpoint']);
        $this->assertSame('http://localhost/.well-known/jwks.json', $body['jwks_uri']);
        $this->assertSame(['S256'], $body['code_challenge_methods_supported']);
        $this->assertTrue($body['client_id_metadata_document_supported']);
        $this->assertTrue($body['authorization_response_iss_parameter_supported']);
        $this->assertContains('none', $body['token_endpoint_auth_methods_supported']);
        $this->assertSame(['code'], $body['response_types_supported']);
        $this->assertArrayNotHasKey('userinfo_endpoint', $body);
    }

    #[Test]
    public function openid_configuration_carries_the_same_fields(): void
    {
        $oauth = $this->document('/.well-known/oauth-authorization-server');
        $oidc = $this->document('/.well-known/openid-configuration');

        foreach ($oauth as $key => $value) {
            $this->assertSame($value, $oidc[$key], $key);
        }

        $this->assertSame(['S256'], $oidc['code_challenge_methods_supported']);
        $this->assertSame('http://localhost/oauth/userinfo', $oidc['userinfo_endpoint']);
    }

    #[Test]
    public function registration_endpoint_is_omitted_when_dynamic_registration_is_off(): void
    {
        $this->setting('flarum-oauth-provider.allow_self_registered_clients', '0');

        $this->assertArrayNotHasKey('registration_endpoint', $this->document('/.well-known/oauth-authorization-server'));
        $this->assertArrayNotHasKey('registration_endpoint', $this->document('/.well-known/openid-configuration'));
    }
}
