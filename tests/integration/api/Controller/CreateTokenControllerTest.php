<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Api\Controller\CreateTokenController;
use Flarum\Http\AccessToken;

class CreateTokenControllerTest extends ApiControllerTestCase
{
    protected $controller = CreateTokenController::class;

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
        $response = $this->call($this->controller, null, [], [
            'identification' => 'normal',
            'password' => 'too-obscure'
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(2, $data['userId']);

        $token = $data['token'];

        $this->assertEquals(2, AccessToken::findOrFail($token)->user_id);
    }
}
