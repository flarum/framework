<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class ConfirmErasureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
        );

        $this->setting('mail_driver', 'log');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'processed_user', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'processed@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'manual_user', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'manual@machine.local', 'is_email_confirmed' => 1],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 2, 'verification_token' => 'abc123', 'status' => 'awaiting_user_confirmation', 'reason' => 'I want to be forgotten', 'created_at' => Carbon::now()],
                ['id' => 2, 'user_id' => 4, 'verification_token' => 'processed-token', 'status' => 'processed', 'reason' => null, 'created_at' => Carbon::now()->subDays(35), 'user_confirmed_at' => Carbon::now()->subDays(35)],
                ['id' => 3, 'user_id' => 5, 'verification_token' => 'manual-token', 'status' => 'manual', 'reason' => null, 'created_at' => Carbon::now()->subDays(35), 'user_confirmed_at' => Carbon::now()->subDays(35)],
            ],
        ]);

        $this->extension('flarum-gdpr');
    }

    protected function loginUser(string $username = 'normal', string $password = 'too-obscure'): ResponseInterface
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => $username,
                    'password' => $password,
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), "Failed to login as '{$username}' using password '{$password}'");

        return $response;
    }

    /**
     * Submit the form on the page the emailed link opens.
     */
    protected function confirm(string $token, ?ResponseInterface $loginResponse = null): ResponseInterface
    {
        if ($loginResponse) {
            return $this->send(
                $this->request('POST', "/gdpr/erasure/confirm/$token", ['cookiesFrom' => $loginResponse])
                    ->withAttribute('bypassCsrfToken', true)
            );
        }

        return $this->send(
            $this->requestWithCsrfToken($this->request('POST', "/gdpr/erasure/confirm/$token"))
        );
    }

    #[Test]
    public function opening_the_link_asks_for_confirmation_without_confirming()
    {
        // Mail security scanners open links in incoming mail, so opening
        // the link must not be what confirms the request.
        $response = $this->send(
            $this->request('GET', '/gdpr/erasure/confirm/abc123')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('method="POST"', $body);
        $this->assertStringContainsString('name="csrfToken"', $body);

        $erasureRequest = ErasureRequest::query()->find(1);
        $this->assertEquals('awaiting_user_confirmation', $erasureRequest->status);
        $this->assertNull($erasureRequest->user_confirmed_at);
        $this->assertEquals('abc123', $erasureRequest->verification_token);
    }

    #[Test]
    public function link_with_incorrect_token_is_not_found()
    {
        $response = $this->send(
            $this->request('GET', '/gdpr/erasure/confirm/wrong-token')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function confirming_requires_a_csrf_token()
    {
        $response = $this->send(
            $this->request('POST', '/gdpr/erasure/confirm/abc123')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('awaiting_user_confirmation', ErasureRequest::query()->find(1)->status);
    }

    #[Test]
    public function guest_cannot_confirm_erasure_without_correct_token()
    {
        $response = $this->confirm('wrong-token');

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_can_confirm_erasure_with_correct_token()
    {
        $response = $this->confirm('abc123');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost?erasureRequestConfirmed=1', $response->getHeaderLine('Location'));

        $erasureRequest = ErasureRequest::query()->find(1);
        $this->assertNotNull($erasureRequest);
        $this->assertEquals('user_confirmed', $erasureRequest->status);
        $this->assertNotNull($erasureRequest->user_confirmed_at);
        $this->assertNull($erasureRequest->verification_token);
    }

    #[Test]
    public function user_cannot_confirm_erasure_with_incorrect_token()
    {
        $response = $this->confirm('wrong-token', $this->loginUser());

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_can_confirm_erasure_with_correct_token()
    {
        $response = $this->confirm('abc123', $this->loginUser());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost?erasureRequestConfirmed=1', $response->getHeaderLine('Location'));

        $erasureRequest = ErasureRequest::query()->find(1);
        $this->assertNotNull($erasureRequest);
        $this->assertEquals('user_confirmed', $erasureRequest->status);
        $this->assertNotNull($erasureRequest->user_confirmed_at);
        $this->assertNull($erasureRequest->verification_token);
    }

    #[Test]
    public function confirmation_ip_is_stored()
    {
        $response = $this->confirm('abc123');

        $this->assertEquals(302, $response->getStatusCode());

        $erasureRequest = ErasureRequest::query()->find(1);
        $this->assertNotNull($erasureRequest->confirmation_ip);
    }

    #[Test]
    public function processed_request_returns_422()
    {
        $this->assertEquals(422, $this->send($this->request('GET', '/gdpr/erasure/confirm/processed-token'))->getStatusCode());
        $this->assertEquals(422, $this->confirm('processed-token')->getStatusCode());
    }

    #[Test]
    public function manual_request_returns_422()
    {
        $this->assertEquals(422, $this->send($this->request('GET', '/gdpr/erasure/confirm/manual-token'))->getStatusCode());
        $this->assertEquals(422, $this->confirm('manual-token')->getStatusCode());
    }

    #[Test]
    public function different_user_cannot_confirm_erasure_for_user()
    {
        $response = $this->confirm('abc123', $this->loginUser('moderator'));

        $this->assertEquals(422, $response->getStatusCode());
    }
}
