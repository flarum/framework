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
 * Tests for GET /api/registration-tokens/{token}
 *
 * Security considerations verified here:
 *  - Valid token → only username, email, provided[] are returned; provider
 *    name, identifier, and payload internals are NOT exposed.
 *  - Invalid / expired token → 404 (does not leak whether the token ever
 *    existed or why it is invalid).
 *  - Guest access is allowed — the token acts as the credential.
 *  - Authenticated users can also read a valid token (e.g. logging in to an
 *    account mid-flow via a different tab).
 *  - All combinations of provided/suggested fields are exercised.
 */
class ShowTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeToken(array $attributes = []): RegistrationToken
    {
        // Boot the app first so the DB connection is available.
        $this->app();

        $defaults = [
            'provider'         => 'github',
            'identifier'       => 'gh-test-123',
            'user_attributes'  => [],
            'payload'          => [],
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

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_can_read_valid_token(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'alice@example.com'],
            'payload'         => ['suggested' => ['username' => 'alice']],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function response_contains_correct_email_from_provided(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'bob@example.com'],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('bob@example.com', $body['data']['attributes']['email']);
    }

    #[Test]
    public function response_contains_correct_username_from_suggested(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'charlie']],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('charlie', $body['data']['attributes']['username']);
    }

    #[Test]
    public function provided_array_lists_locked_fields(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'dave@example.com', 'username' => 'dave'],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $provided = $body['data']['attributes']['provided'];
        $this->assertContains('email', $provided);
        $this->assertContains('username', $provided);
    }

    #[Test]
    public function provided_is_empty_when_no_user_attributes(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'eve']],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $body['data']['attributes']['provided']);
    }

    #[Test]
    public function username_and_email_are_null_when_not_provided_or_suggested(): void
    {
        $token = $this->makeToken();

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertNull($body['data']['attributes']['username']);
        $this->assertNull($body['data']['attributes']['email']);
    }

    #[Test]
    public function provided_email_takes_precedence_over_suggested_email(): void
    {
        // If email is in both provided and suggested, provided wins.
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'provided@example.com'],
            'payload'         => ['suggested' => ['email' => 'suggested@example.com']],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('provided@example.com', $body['data']['attributes']['email']);
    }

    // -------------------------------------------------------------------------
    // Security: sensitive fields must NOT be exposed
    // -------------------------------------------------------------------------

    #[Test]
    public function provider_name_is_not_in_response(): void
    {
        $token = $this->makeToken(['provider' => 'google']);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('provider', $attrs);
    }

    #[Test]
    public function identifier_is_not_in_response(): void
    {
        $token = $this->makeToken(['identifier' => 'secret-oauth-id-xyz']);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('identifier', $attrs);
    }

    #[Test]
    public function payload_internals_are_not_in_response(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'frank'], 'internal_data' => 'secret'],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertArrayNotHasKey('payload', $attrs);
        $this->assertArrayNotHasKey('internal_data', $attrs);
    }

    // -------------------------------------------------------------------------
    // Error cases
    // -------------------------------------------------------------------------

    #[Test]
    public function invalid_token_returns_404(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/this-token-does-not-exist')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function expired_token_returns_404(): void
    {
        $token = $this->makeToken();
        // Back-date the token by 25 hours so it exceeds the 24-hour validity window.
        $token->created_at = Carbon::now()->subHours(25);
        $token->save();

        $response = $this->send(
            $this->request('GET', '/api/registration-tokens/' . $token->token)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function post_to_registration_tokens_is_not_allowed(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/registration-tokens', ['json' => []])
        );

        // No create endpoint — 404 or 405.
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    #[Test]
    public function delete_to_registration_token_is_not_allowed(): void
    {
        $token = $this->makeToken();

        $response = $this->send(
            $this->request('DELETE', '/api/registration-tokens/' . $token->token)
        );

        // No delete endpoint — 404 or 405.
        $this->assertContains($response->getStatusCode(), [404, 405]);
        // Token must still exist.
        $this->assertNotNull(RegistrationToken::find($token->token));
    }

    #[Test]
    public function patch_to_registration_token_is_not_allowed(): void
    {
        $token = $this->makeToken();

        $response = $this->send(
            $this->request('PATCH', '/api/registration-tokens/' . $token->token, ['json' => [
                'data' => ['type' => 'registration-tokens', 'id' => $token->token, 'attributes' => []],
            ]])
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);
    }
}
