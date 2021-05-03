<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\csrf_protection;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class RequireCsrfTokenTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'api_keys' => [
                ['user_id' => 1, 'key' => 'superadmin'],
            ]
        ]);

        $this->setting('csrf_test', 1);
    }

    /**
     * @test
     */
    public function error_when_doing_cookie_auth_without_csrf_token()
    {
        $auth = $this->send(
            $this->request(
                'POST',
                '/login',
                [
                    'json' => ['identification' => 'admin', 'password' => 'password'],
                ]
            )
        );

        $response = $this->send(
            $this->request(
                'POST',
                '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['csrf_test' => 2],
                ]
            )
        );

        // Response should be "HTTP 400 Bad Request"
        $this->assertEquals(400, $response->getStatusCode());

        // The response body should contain proper error details
        $body = (string) $response->getBody();
        $this->assertJson($body);

        $json = json_decode($body, true);
        $this->assertCount(1, $json['errors']);
        $this->assertEquals('400', $json['errors'][0]['status']);
        $this->assertEquals('csrf_token_mismatch', $json['errors'][0]['code']);
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
                'POST',
                '/login',
                [
                    'cookiesFrom' => $initial,
                    'json' => ['identification' => 'admin', 'password' => 'password'],
                ]
            )->withHeader('X-CSRF-Token', $token)
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request(
                'POST',
                '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['csrf_test' => 2],
                ]
            )->withHeader('X-CSRF-Token', $token)
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            2,
            $this->database()->table('settings')->where('key', 'csrf_test')->first()->value
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
                'POST',
                '/login',
                [
                    'cookiesFrom' => $initial,
                    'json' => ['identification' => 'admin', 'password' => 'password', 'csrfToken' => $token],
                ]
            )
        );

        $token = $auth->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request(
                'POST',
                '/api/settings',
                [
                    'cookiesFrom' => $auth,
                    'json' => ['csrf_test' => 2, 'csrfToken' => $token],
                ]
            )
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            2,
            $this->database()->table('settings')->where('key', 'csrf_test')->first()->value
        );
    }

    /**
     * @test
     */
    public function master_api_token_does_not_need_csrf_token()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/settings',
                [
                    'json' => ['csrf_test' => 2],
                ]
            )->withHeader('Authorization', 'Token superadmin')
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            2,
            $this->database()->table('settings')->where('key', 'csrf_test')->first()->value
        );
    }

    /**
     * @test
     */
    public function access_token_does_not_need_csrf_token()
    {
        $this->database()->table('access_tokens')->insert(
            ['token' => 'myaccesstoken', 'user_id' => 1, 'type' => 'developer']
        );

        $response = $this->send(
            $this->request(
                'POST',
                '/api/settings',
                [
                    'json' => ['csrf_test' => 2],
                ]
            )->withHeader('Authorization', 'Token myaccesstoken')
        );

        // Successful response?
        $this->assertEquals(204, $response->getStatusCode());

        // Was the setting actually changed in the database?
        $this->assertEquals(
            2,
            $this->database()->table('settings')->where('key', 'csrf_test')->first()->value
        );

        $this->database()->table('access_tokens')->where('token', 'myaccesstoken')->delete();
    }
}
