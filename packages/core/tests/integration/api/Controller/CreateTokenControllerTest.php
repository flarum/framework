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

    /**
     * @test
     */
    public function user_generates_token()
    {
        $user = $this->getNormalUser();

        $response = $this->call($this->controller, null, [], [
            'identification' => $user->username,
            'password' => $this->userAttributes['password']
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals($user->id, $data['userId']);

        $token = $data['token'];

        $this->assertEquals($user->id, AccessToken::findOrFail($token)->user_id);
    }
}
