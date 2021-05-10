<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class LoginTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->extend(
            (new Extend\Csrf)->exemptRoute('login')
        );

        $this->prepareDatabase([
            'users' => [
                $this->normalUser()
            ]
        ]);
    }

    /**
     * @test
     */
    public function cant_login_without_data()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => []
            ])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cant_login_with_wrong_password()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'incorrect'
                ]
            ])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function can_login_with_data()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure'
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // The response body should contain the user ID...
        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);
        $this->assertEquals(2, $data['userId']);

        // ...and an access token belonging to this user.
        $token = $data['token'];
        $this->assertEquals(2, AccessToken::whereToken($token)->firstOrFail()->user_id);
    }
}
