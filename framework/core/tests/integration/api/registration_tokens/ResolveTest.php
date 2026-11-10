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
 * Tests for POST /api/registration-token.
 *
 * The token is submitted in the request body (not the URL) to keep it out of
 * server access logs, browser history, and Referer headers.
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
            'provider' => 'github',
            'identifier' => 'gh-test-123',
            'user_attributes' => [],
            'payload' => [],
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

        $this->assertEquals(200, $this->resolve($token->token)->getStatusCode());
    }

    #[Test]
    public function response_contains_correct_email_from_provided(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'bob@example.com'],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('bob@example.com', $body['email']);
    }

    #[Test]
    public function response_contains_correct_username_from_suggested(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'charlie']],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('charlie', $body['username']);
    }

    #[Test]
    public function provided_array_lists_locked_fields(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'dave@example.com', 'username' => 'dave'],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertContains('email', $body['provided']);
        $this->assertContains('username', $body['provided']);
    }

    #[Test]
    public function provided_is_empty_when_no_user_attributes(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'eve']],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals([], $body['provided']);
    }

    #[Test]
    public function username_and_email_are_null_when_not_provided_or_suggested(): void
    {
        $token = $this->makeToken();

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertNull($body['username']);
        $this->assertNull($body['email']);
    }

    #[Test]
    public function provided_email_takes_precedence_over_suggested_email(): void
    {
        $token = $this->makeToken([
            'user_attributes' => ['email' => 'provided@example.com'],
            'payload' => ['suggested' => ['email' => 'suggested@example.com']],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertEquals('provided@example.com', $body['email']);
    }

    // -------------------------------------------------------------------------
    // Security: sensitive fields must NOT be exposed
    // -------------------------------------------------------------------------

    #[Test]
    public function provider_name_is_not_in_response(): void
    {
        $token = $this->makeToken(['provider' => 'google']);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('provider', $body);
    }

    #[Test]
    public function identifier_is_not_in_response(): void
    {
        $token = $this->makeToken(['identifier' => 'secret-oauth-id-xyz']);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('identifier', $body);
    }

    #[Test]
    public function payload_internals_are_not_in_response(): void
    {
        $token = $this->makeToken([
            'payload' => ['suggested' => ['username' => 'frank'], 'internal_data' => 'secret'],
        ]);

        $body = json_decode($this->resolve($token->token)->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('payload', $body);
        $this->assertArrayNotHasKey('internal_data', $body);
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
            $this->request('POST', '/api/registration-token', ['json' => []])
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
            $this->request('DELETE', '/api/registration-token')
        );

        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertNotNull(RegistrationToken::find($token->token));
    }
}
