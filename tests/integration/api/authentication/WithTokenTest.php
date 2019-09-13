<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\authentication;

use Flarum\Http\AccessToken;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class WithTokenTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_generates_token()
    {
        $response = $this->send(
            $this->request(
                'POST', '/api/token',
                [
                    'json' => [
                        'identification' => 'normal',
                        'password' => 'too-obscure'
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        // The response body should contain the user ID...
        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);
        $this->assertEquals(2, $data['userId']);

        // ...and an access token belonging to this user.
        $token = $data['token'];
        $this->assertEquals(2, AccessToken::findOrFail($token)->user_id);
    }
}
