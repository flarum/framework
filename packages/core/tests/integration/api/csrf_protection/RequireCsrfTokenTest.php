<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\csrf_protection;

use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\Application;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Zend\Diactoros\CallbackStream;
use Zend\Diactoros\ServerRequest;

class RequireCsrfTokenTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ],
            'groups' => [
                $this->adminGroup(),
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 3],
            ],
            'access_tokens' => [
                ['user_id' => 1, 'token' => 'superadmin', 'lifetime_seconds' => 30],
            ],
            'settings' => [
                ['key' => 'mail_driver', 'value' => 'smtp'],
                ['key' => 'version', 'value' => Application::VERSION],
            ],
        ]);
    }

    /**
     * @test
     */
    public function error_when_doing_cookie_auth_without_csrf_token()
    {
        $auth = $this->server->handle(
            (new ServerRequest([], [], '/login', 'POST'))
                ->withBody(new CallbackStream(function () {
                    return '{"identification": "admin", "password": "password"}';
                }))
                ->withHeader('Content-Type', 'application/json')
        );

        $cookies = array_reduce(
            $auth->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();
                return $memo;
            },
            []
        );

        $response = $this->server->handle(
            (new ServerRequest([], [], '/api/settings', 'POST'))
                ->withBody(new CallbackStream(function () {
                    return '{"mail_driver": "log"}';
                }))
                ->withCookieParams($cookies)
                ->withHeader('Content-Type', 'application/json')
        );

        // Response should be "HTTP 400 Bad Request"
        $this->assertEquals(400, $response->getStatusCode());

        // The response body should contain proper error details
        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                ['status' => '400', 'code' => 'csrf_token_mismatch'],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function cookie_auth_succeeds_with_csrf_token_in_header()
    {
        $initial = $this->server->handle(
            (new ServerRequest([], [], '/', 'GET'))
        );

        $token = $initial->getHeaderLine('X-CSRF-Token');
        $cookies = array_reduce(
            $initial->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();
                return $memo;
            },
            []
        );

        $auth = $this->server->handle(
            (new ServerRequest([], [], '/login', 'POST'))
                ->withBody(new CallbackStream(function () {
                    return '{"identification": "admin", "password": "password"}';
                }))
                ->withCookieParams($cookies)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-CSRF-Token', $token)
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');
        $cookies = array_reduce(
            $auth->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();
                return $memo;
            },
            []
        );

        $response = $this->server->handle(
            (new ServerRequest([], [], '/api/settings', 'POST'))
                ->withBody(new CallbackStream(function () {
                    return '{"mail_driver": "log"}';
                }))
                ->withCookieParams($cookies)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-CSRF-Token', $token)
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            'log',
            $this->database()->table('settings')->where('key', 'mail_driver')->first()->value
        );
    }

    /**
     * @test
     */
    public function cookie_auth_succeeds_with_csrf_token_in_body()
    {
        $initial = $this->server->handle(
            (new ServerRequest([], [], '/', 'GET'))
        );

        $token = $initial->getHeaderLine('X-CSRF-Token');
        $cookies = array_reduce(
            $initial->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();
                return $memo;
            },
            []
        );

        $auth = $this->server->handle(
            (new ServerRequest([], [], '/login', 'POST'))
                ->withBody(new CallbackStream(function () use ($token) {
                    return '{"identification": "admin", "password": "password", "csrfToken": "'.$token.'"}';
                }))
                ->withCookieParams($cookies)
                ->withHeader('Content-Type', 'application/json')
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');
        $cookies = array_reduce(
            $auth->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();
                return $memo;
            },
            []
        );

        $response = $this->server->handle(
            (new ServerRequest([], [], '/api/settings', 'POST'))
                ->withBody(new CallbackStream(function () use ($token) {
                    return '{"mail_driver": "log", "csrfToken": "'.$token.'"}';
                }))
                ->withCookieParams($cookies)
                ->withHeader('Content-Type', 'application/json')
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            'log',
            $this->database()->table('settings')->where('key', 'mail_driver')->first()->value
        );
    }

    /**
     * @test
     */
    public function master_api_token_does_not_need_csrf_token()
    {
        $response = $this->server->handle(
            (new ServerRequest([], [], '/api/settings', 'POST'))
                ->withBody(new CallbackStream(function () {
                    return '{"mail_driver": "log"}';
                }))
                ->withHeader('Authorization', 'Token superadmin')
                ->withHeader('Content-Type', 'application/json')
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            'log',
            $this->database()->table('settings')->where('key', 'mail_driver')->first()->value
        );
    }
}
