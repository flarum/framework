<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\authentication;

use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class WithTokenTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
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
                'POST',
                '/api/token',
                [
                    'json' => [
                        'identification' => 'normal',
                        'password' => 'too-obscure'
                    ],
                ]
            )
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

    /**
     * @test
     */
    public function failure_with_invalid_credentials()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/token',
                [
                    'json' => [
                        'identification' => 'normal',
                        'password' => 'too-incorrect'
                    ],
                ]
            )
        );

        // HTTP 401 signals an authentication problem
        $this->assertEquals(401, $response->getStatusCode());

        // The response body should contain an error code
        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);
        $this->assertCount(1, $data['errors']);
        $this->assertEquals('not_authenticated', $data['errors'][0]['code']);
    }
}
