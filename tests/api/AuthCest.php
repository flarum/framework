<?php
use \ApiTester;

use Laracasts\TestDummy\Factory;

class AuthCest
{
    protected $endpoint = '/api/auth';

    public function loginWithEmail(ApiTester $I)
    {
        $I->wantTo('login via API with email');

        $user = $I->haveAnAccount([
            'email' => 'foo@bar.com',
            'password' => 'pass7word'
        ]);

        $I->login('foo@bar.com', 'pass7word');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        $token = $I->grabDataFromJsonResponse('token');
        $userId = $I->grabDataFromJsonResponse('userId');
        $I->assertNotEmpty($token);
        
        $loggedIn = User::where('remember_token', $token)->where('id', $userId)->first();
        $I->assertEquals($user->id, $loggedIn->id);
    }

    public function loginWithUsername(ApiTester $I)
    {
        $I->wantTo('login via API with username');

        $user = $I->haveAnAccount([
            'username' => 'tobscure',
            'password' => 'pass7word'
        ]);

        $I->login('tobscure', 'pass7word');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        $token = $I->grabDataFromJsonResponse('token');
        $userId = $I->grabDataFromJsonResponse('userId');
        $I->assertNotEmpty($token);

        $loggedIn = User::where('remember_token', $token)->where('id', $userId)->first();
        $I->assertEquals($user->id, $loggedIn->id);
    }

    public function invalidLogin(ApiTester $I)
    {
        $user = $I->haveAnAccount([
            'email' => 'foo@bar.com',
            'password' => 'pass7word'
        ]);

        $I->login('foo@bar.com', 'incorrect');
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
    }
}