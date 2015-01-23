<?php namespace Flarum\Api\Actions\Auth;

use Event;
use Response;
use Auth;

use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;

class Login extends Base
{
    /**
     * Log in and return a token.
     *
     * @return Response
     */
    protected function run()
    {
        $identification = $this->input('identification');
        $password = $this->input('password');
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $identification, 'password' => $password];

        if (! Auth::attempt($credentials, true)) {
            return $this->respondWithError('invalidLogin', 401);
        }

        $user = Auth::user();

        return Response::json([
            'token' => $user->getRememberToken(),
            'userId' => $user->id
        ]);
    }
}
