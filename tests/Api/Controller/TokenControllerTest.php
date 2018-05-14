<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\TokenController;
use Flarum\Http\AccessToken;
use Flarum\Tests\Test\Concerns\RetrievesAuthorizedUsers;

class TokenControllerTest extends ApiControllerTestCase
{
    use RetrievesAuthorizedUsers;

    protected $controller = TokenController::class;

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
