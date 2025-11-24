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
                ->exemptRoute('gdpr.erasure.confirm')
        );

        $this->setting('mail_driver', 'log');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 2, 'verification_token' => 'abc123', 'status' => 'awaiting_user_confirmation', 'reason' => 'I want to be forgotten', 'created_at' => Carbon::now()],
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
                    'password'       => $password,
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), "Failed to login as '{$username}' using password '{$password}'");

        return $response;
    }

    #[Test]
    public function guest_cannot_confirm_erasure_without_correct_token()
    {
        $response = $this->send(
            $this->request(
                'GET',
                '/gdpr/erasure/confirm/wrong-token'
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_can_confirm_erasure_with_correct_token()
    {
        $response = $this->send(
            $this->request(
                'GET',
                '/gdpr/erasure/confirm/abc123'
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost?erasureRequestConfirmed=1', $response->getHeaderLine('Location'));

        $erasureRequest = ErasureRequest::query()->where('verification_token', 'abc123')->first();
        $this->assertNotNull($erasureRequest);
        $this->assertEquals('user_confirmed', $erasureRequest->status);
        $this->assertNotNull($erasureRequest->user_confirmed_at);
    }

    #[Test]
    public function user_cannot_confirm_erasure_with_incorrect_token()
    {
        $loginResponse = $this->loginUser();

        $response = $this->send(
            $this->request(
                'GET',
                '/gdpr/erasure/confirm/wrong-token',
                [
                    'cookiesFrom' => $loginResponse,
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function user_can_confirm_erasure_with_correct_token()
    {
        $loginResponse = $this->loginUser();

        $response = $this->send(
            $this->request(
                'GET',
                '/gdpr/erasure/confirm/abc123',
                [
                    'cookiesFrom' => $loginResponse,
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost?erasureRequestConfirmed=1', $response->getHeaderLine('Location'));

        $erasureRequest = ErasureRequest::query()->where('verification_token', 'abc123')->first();
        $this->assertNotNull($erasureRequest);
        $this->assertEquals('user_confirmed', $erasureRequest->status);
        $this->assertNotNull($erasureRequest->user_confirmed_at);
    }

    #[Test]
    public function different_user_cannot_confirm_erasure_for_user()
    {
        $loginResponse = $this->loginUser('moderator');

        $response = $this->send(
            $this->request(
                'GET',
                '/gdpr/erasure/confirm/abc123',
                [
                    'cookiesFrom' => $loginResponse,
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(422, $response->getStatusCode());
    }
}
