<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\EmailToken;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * Can a precognitive request be used to guess a password without the normal
 * throttles? normalUser()'s password is "too-obscure".
 */
class PasswordOracleTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function emailChange(string $password, ?string $ip = null): \Psr\Http\Message\ServerRequestInterface
    {
        return $this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['email' => 'new@machine.local']],
            'meta' => ['password' => $password],
        ], 2, $ip));
    }

    /**
     * The email-change password check lives in the Update endpoint's visible()
     * callback, so precognition reaches it: wrong → 401, right → 204. Validate-
     * only requests are capped by the precognition limiter (60/min/user).
     * No email token is created and no mail is sent on the correct guess.
     */
    #[Test]
    public function email_change_password_oracle_is_capped_by_the_precognition_limiter()
    {
        $mails = 0;
        $this->app()->getContainer()->make(Dispatcher::class)->listen(\Illuminate\Mail\Events\MessageSending::class, function () use (&$mails) {
            $mails++;
        });

        $this->assertEquals(401, $this->send($this->emailChange('wrong-guess'))->getStatusCode());
        $this->assertEquals(204, $this->send($this->emailChange('too-obscure'))->getStatusCode());

        for ($i = 2; $i < 60; $i++) {
            $this->send($this->emailChange('wrong-'.$i, '10.0.0.'.($i % 250)));
        }

        // 61st attempt, even with the right password and from yet another IP: limited.
        $response = $this->send($this->emailChange('too-obscure', '10.9.9.9'));
        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());

        $this->assertEquals(0, EmailToken::query()->count());
        $this->assertEquals(0, $mails);
        $this->assertEquals('normal@machine.local', User::query()->find(2)->email);
    }

    /**
     * GDPR's password check is in the Create endpoint's before() hook, which
     * precognition skips: right and wrong passwords answer identically, so it
     * is no oracle — and no erasure request is stored.
     */
    #[Test]
    public function gdpr_erasure_precognition_is_no_password_oracle()
    {
        $this->unlimitedPrecognition();

        $request = fn (string $password) => $this->precognitive($this->jsonRequest('POST', '/api/user-erasure-requests', [
            'data' => ['type' => 'user-erasure-requests', 'attributes' => ['reason' => 'bye']],
            'meta' => ['password' => $password],
        ], 2));

        $wrong = $this->send($request('wrong'));
        $right = $this->send($request('too-obscure'));

        $this->assertEquals($right->getStatusCode(), $wrong->getStatusCode());
        $this->assertEquals((string) $right->getBody(), (string) $wrong->getBody());
        $this->assertEquals(0, $this->database()->table('gdpr_erasure')->count());
    }

    /**
     * The login route is not a Create/Update endpoint: a precognitive login is
     * refused with 400 and issues no token, so it can't be a side channel
     * around the LoginThrottler either.
     */
    #[Test]
    public function precognitive_login_is_refused_and_issues_nothing()
    {
        $tokens = $this->database()->table('access_tokens')->count();

        foreach (['too-obscure', 'wrong'] as $password) {
            $response = $this->send($this->precognitive($this->request('POST', '/api/token', [
                'json' => ['identification' => 'normal', 'password' => $password],
            ])->withAttribute('bypassCsrfToken', true)));

            $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        }

        $this->assertEquals($tokens, $this->database()->table('access_tokens')->count());
    }
}
