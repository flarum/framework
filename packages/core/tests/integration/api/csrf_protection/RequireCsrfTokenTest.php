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

use Flarum\Foundation\Application;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

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
        $auth = $this->send(
            $this->request(
                'POST', '/login',
                [
                    'json' => ['identification' => 'admin', 'password' => 'password'],
                ]
            )
        );

        $response = $this->send(
            $this->request(
                'POST', '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['mail_driver' => 'log'],
                ]
            )
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
        $initial = $this->send(
            $this->request('GET', '/')
        );

        $token = $initial->getHeaderLine('X-CSRF-Token');

        $auth = $this->send(
            $this->request(
                'POST', '/login',
                [
                    'cookiesFrom' => $initial,
                    'json' => ['identification' => 'admin', 'password' => 'password'],
                ]
            )->withHeader('X-CSRF-Token', $token)
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request(
                'POST', '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['mail_driver' => 'log'],
                ]
            )->withHeader('X-CSRF-Token', $token)
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
        $initial = $this->send(
            $this->request('GET', '/')
        );

        $token = $initial->getHeaderLine('X-CSRF-Token');

        $auth = $this->send(
            $this->request(
                'POST', '/login',
                [
                    'cookiesFrom' => $initial,
                    'json' => ['identification' => 'admin', 'password' => 'password', 'csrfToken' => $token],
                ]
            )
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request(
                'POST', '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['mail_driver' => 'log', 'csrfToken' => $token],
                ]
            )
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
        $response = $this->send(
            $this->request(
                'POST', '/api/settings',
                [
                    'json' => ['mail_driver' => 'log'],
                ]
            )->withHeader('Authorization', 'Token superadmin')
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
