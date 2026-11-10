<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\authentication;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Flarum\Extend;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Foundation\ValidationException;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Throttler\LoginThrottler;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginThrottleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())->exemptRoute('login')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1, 'password' => $this->normalUser()['password']],
            ],
            ApiKey::class => [
                ['key' => 'mastertoken', 'user_id' => null, 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        // The counts live in the cache, which outlives each test. Leave none
        // behind for the tests that run after these.
        $this->app()->getContainer()->make(Repository::class)->clear();

        LoginThrottler::$decaySeconds = 60;

        parent::tearDown();
    }

    #[Test]
    public function failed_logins_for_one_account_from_one_address_are_limited()
    {
        foreach (range(1, 5) as $attempt) {
            $this->assertEquals(401, $this->logIn('normal', 'incorrect')->getStatusCode(), "Attempt $attempt");
        }

        $response = $this->logIn('normal', 'incorrect');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('too_many_requests', json_decode((string) $response->getBody(), true)['errors'][0]['code']);
    }

    #[Test]
    public function the_right_password_is_refused_while_limited()
    {
        $this->failLogIns('normal', 5);

        $this->assertEquals(429, $this->logIn('normal', 'too-obscure')->getStatusCode());
    }

    #[Test]
    public function another_account_can_log_in_from_the_same_address()
    {
        $this->failLogIns('normal', 5);

        $this->assertEquals(200, $this->logIn('other', 'too-obscure')->getStatusCode());
    }

    #[Test]
    public function the_same_account_can_log_in_from_another_address()
    {
        $this->failLogIns('normal', 5, '192.0.2.1');

        $this->assertEquals(200, $this->logIn('normal', 'too-obscure', '192.0.2.2')->getStatusCode());
    }

    #[Test]
    public function an_address_trying_many_accounts_is_limited()
    {
        foreach (range(1, 30) as $attempt) {
            $this->assertEquals(401, $this->logIn("nobody-$attempt", 'incorrect')->getStatusCode(), "Attempt $attempt");
        }

        $this->assertEquals(429, $this->logIn('normal', 'too-obscure')->getStatusCode());
        $this->assertEquals(200, $this->logIn('normal', 'too-obscure', '192.0.2.2')->getStatusCode());
    }

    #[Test]
    public function successful_logins_are_not_counted()
    {
        foreach (range(1, 10) as $attempt) {
            $this->assertEquals(200, $this->logIn('normal', 'too-obscure')->getStatusCode(), "Attempt $attempt");
        }
    }

    #[Test]
    public function the_limit_expires()
    {
        // The window starts at the first failure, and five password checks
        // can take a second, so leave room for them.
        LoginThrottler::$decaySeconds = 3;

        $this->failLogIns('normal', 5);
        $this->assertEquals(429, $this->logIn('normal', 'too-obscure')->getStatusCode());

        sleep(4);

        $this->assertEquals(200, $this->logIn('normal', 'too-obscure')->getStatusCode());
    }

    #[Test]
    public function the_identification_is_counted_whatever_its_case_and_surrounding_spaces()
    {
        foreach (['normal', 'NORMAL', ' normal', 'Normal ', 'nOrMaL'] as $identification) {
            $this->assertEquals(401, $this->logIn($identification, 'incorrect')->getStatusCode());
        }

        $this->assertEquals(429, $this->logIn('normal', 'too-obscure')->getStatusCode());
    }

    #[Test]
    public function the_login_form_and_the_api_share_one_limit()
    {
        $this->failLogIns('normal', 3);

        foreach (range(1, 2) as $attempt) {
            $this->assertEquals(401, $this->logIn('normal', 'incorrect', path: '/login')->getStatusCode(), "Attempt $attempt");
        }

        $this->assertEquals(429, $this->logIn('normal', 'too-obscure', path: '/login')->getStatusCode());
        $this->assertEquals(429, $this->logIn('normal', 'too-obscure')->getStatusCode());
    }

    #[Test]
    public function requests_made_with_an_api_key_are_not_limited()
    {
        foreach (range(1, 8) as $attempt) {
            $response = $this->send(
                $this->logInRequest('normal', 'incorrect')
                    ->withAddedHeader('Authorization', 'Token mastertoken; userId=1')
            );

            $this->assertEquals(401, $response->getStatusCode(), "Attempt $attempt");
        }

        $this->assertEquals(200, $this->logIn('normal', 'too-obscure')->getStatusCode());
    }

    /**
     * As extensions that take over the route do, such as ianm/twofactor.
     */
    #[Test]
    public function failures_from_a_replacement_token_controller_are_counted()
    {
        $this->extend(
            (new Extend\Routes('api'))
                ->remove('token')
                ->post('/token', 'token', fn () => new class implements RequestHandlerInterface {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        throw new NotAuthenticatedException();
                    }
                })
        );

        $this->failLogIns('normal', 5);

        $this->assertEquals(429, $this->logIn('normal', 'incorrect')->getStatusCode());
    }

    /**
     * Such as a missing CAPTCHA answer, or a second factor still to be given.
     */
    #[Test]
    public function rejections_other_than_wrong_credentials_are_not_counted()
    {
        $this->extend(
            (new Extend\Routes('api'))
                ->remove('token')
                ->post('/token', 'token', fn () => new class implements RequestHandlerInterface {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        throw new ValidationException(['twoFactorToken' => 'two_factor_required']);
                    }
                })
        );

        foreach (range(1, 8) as $attempt) {
            $this->assertEquals(422, $this->logIn('normal', 'too-obscure')->getStatusCode(), "Attempt $attempt");
        }
    }

    /**
     * The login page shown in maintenance mode, which posts a plain HTML form.
     */
    #[Test]
    public function the_maintenance_login_page_says_why_a_login_was_refused()
    {
        $this->config('offline', MaintenanceMode::LOW);

        foreach (range(1, 5) as $attempt) {
            $this->send($this->htmlLogInRequest('normal', 'incorrect'));
        }

        $response = $this->send($this->htmlLogInRequest('normal', 'too-obscure'));

        $this->assertEquals(302, $response->getStatusCode());

        $page = $this->send($this->request('GET', '/maintenance/login', ['cookiesFrom' => $response]));

        $this->assertStringContainsString('Too many failed attempts', (string) $page->getBody());
    }

    protected function failLogIns(string $identification, int $times, string $ip = '127.0.0.1'): void
    {
        foreach (range(1, $times) as $attempt) {
            $this->assertEquals(401, $this->logIn($identification, 'incorrect', $ip)->getStatusCode(), "Failed login $attempt");
        }
    }

    protected function logIn(string $identification, string $password, string $ip = '127.0.0.1', string $path = '/api/token'): ResponseInterface
    {
        return $this->send($this->logInRequest($identification, $password, $ip, $path));
    }

    protected function htmlLogInRequest(string $identification, string $password): ServerRequestInterface
    {
        return (new ServerRequest(['REMOTE_ADDR' => '127.0.0.1'], [], '/login', 'POST'))
            ->withHeader('Accept', 'text/html')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody(compact('identification', 'password'));
    }

    protected function logInRequest(string $identification, string $password, string $ip = '127.0.0.1', string $path = '/api/token'): ServerRequestInterface
    {
        return $this->requestWithJsonBody(
            new ServerRequest(['REMOTE_ADDR' => $ip], [], $path, 'POST'),
            compact('identification', 'password')
        );
    }
}
