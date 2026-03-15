<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\registration_tokens;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;
use Flarum\User\RegistrationToken;
use PHPUnit\Framework\Attributes\Test;

/**
<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
 * Tests for GET /api/registration-tokens/{token}.
=======
 * Tests for POST /api/registration-token
 *
 * The token is submitted in the request body (not the URL) to keep it out of
 * server access logs, browser history, and Referer headers.
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
 *
 * Security considerations verified here:
 *  - Valid token → only username, email, provided[] are returned; provider
 *    name, identifier, and payload internals are NOT exposed.
 *  - Invalid / expired token → 404 (does not leak whether the token ever
 *    existed or why it is invalid).
 *  - Missing token → 404 (empty string treated as invalid).
 *  - Guest access is allowed — the token acts as the credential.
 *  - GET, DELETE, PATCH to this endpoint are rejected (405/404).
 *  - All combinations of provided/suggested fields are exercised.
 */
class ResolveTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeToken(array $attributes = []): RegistrationToken
    {
        $this->app();

        $defaults = [
<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
            'provider' => 'github',
            'identifier' => 'gh-test-123',
            'user_attributes' => [],
            'payload' => [],
=======
            'provider'        => 'github',
            'identifier'      => 'gh-test-123',
            'user_attributes' => [],
            'payload'         => [],
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
        ];
        $merged = array_merge($defaults, $attributes);

        $token = RegistrationToken::generate(
            $merged['provider'],
            $merged['identifier'],
            $merged['user_attributes'],
            $merged['payload']
        );
        $token->save();

        return $token;
    }

    private function resolve(string $tokenValue): \Psr\Http\Message\ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/registration-token', [
                'json' => ['token' => $tokenValue],
            ])
        );
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_can_resolve_valid_token(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'alice@example.com'],
            'payload' => ['suggested' => ['username' => 'alice']],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $this->assertEquals(200, $response->getStatusCode());
=======
        $this->assertEquals(200, $this->resolve($token->token)->getStatusCode());
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function response_contains_correct_email_from_provided(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'bob@example.com'],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('bob@example.com', $body['data']['attributes']['email']);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('bob@example.com', $body['email']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function response_contains_correct_username_from_suggested(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'charlie']],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('charlie', $body['data']['attributes']['username']);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('charlie', $body['username']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function provided_array_lists_locked_fields(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'dave@example.com', 'username' => 'dave'],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $provided = $body['data']['attributes']['provided'];
        $this->assertContains('email', $provided);
        $this->assertContains('username', $provided);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertContains('email', $body['provided']);
        $this->assertContains('username', $body['provided']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function provided_is_empty_when_no_user_attributes(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'eve']],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $body['data']['attributes']['provided']);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals([], $body['provided']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function username_and_email_are_null_when_not_provided_or_suggested(): void
    {
        $token = $this->makeToken();

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertNull($body['data']['attributes']['username']);
        $this->assertNull($body['data']['attributes']['email']);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertNull($body['username']);
        $this->assertNull($body['email']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function provided_email_takes_precedence_over_suggested_email(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'provided@example.com'],
            'payload' => ['suggested' => ['email' => 'suggested@example.com']],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('provided@example.com', $body['data']['attributes']['email']);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('provided@example.com', $body['email']);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    // -------------------------------------------------------------------------
    // Security: sensitive fields must NOT be exposed
    // -------------------------------------------------------------------------

    #[Test]
    public function provider_name_is_not_in_response(): void
    {
        $token = $this->makeToken(['provider' => 'google']);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('provider', $attrs);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('provider', $body);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function identifier_is_not_in_response(): void
    {
        $token = $this->makeToken(['identifier' => 'secret-oauth-id-xyz']);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('identifier', $attrs);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('identifier', $body);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    #[Test]
    public function payload_internals_are_not_in_response(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'frank'], 'internal_data' => 'secret'],
        ]);

<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/'.$token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('payload', $attrs);
        $this->assertArrayNotHasKey('internal_data', $attrs);
=======
        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('payload', $body);
        $this->assertArrayNotHasKey('internal_data', $body);
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
    }

    // -------------------------------------------------------------------------
    // Error cases
    // -------------------------------------------------------------------------

    #[Test]
    public function invalid_token_returns_404(): void
    {
        $this->assertEquals(404, $this->resolve('this-token-does-not-exist')->getStatusCode());
    }

    #[Test]
    public function expired_token_returns_404(): void
    {
        $token = $this->makeToken();
        $token->created_at = Carbon::now()->subHours(25);
        $token->save();

        $this->assertEquals(404, $this->resolve($token->token)->getStatusCode());
    }

    #[Test]
    public function missing_token_returns_404(): void
    {
        $response = $this->send(
<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
            $this->request('GET', '/api/registration-tokens/'.$token->token)
=======
            $this->request('POST', '/api/registration-token', ['json' => []])
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function get_to_registration_token_is_not_allowed(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/registration-token')
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    #[Test]
    public function delete_to_registration_token_is_not_allowed(): void
    {
        $token = $this->makeToken();

        $response = $this->send(
<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php
            $this->request('DELETE', '/api/registration-tokens/'.$token->token)
=======
            $this->request('DELETE', '/api/registration-token')
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertNotNull(RegistrationToken::find($token->token));
    }
<<<<<<< HEAD:framework/core/tests/integration/api/registration_tokens/ShowTest.php

    #[Test]
    public function patch_to_registration_token_is_not_allowed(): void
    {
        $token = $this->makeToken();

        $response = $this->send(
            $this->request('PATCH', '/api/registration-tokens/'.$token->token, ['json' => [
                'data' => ['type' => 'registration-tokens', 'id' => $token->token, 'attributes' => []],
            ]])
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);
    }
=======
>>>>>>> 4176d3df1 (feat(core): add POST /api/registration-token endpoint for OAuth sign-up pre-population):framework/core/tests/integration/api/registration_tokens/ResolveTest.php
}
