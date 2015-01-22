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
        $identifier = $this->input('identifier');
        $password = $this->input('password');
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $identifier, 'password' => $password];

        if (! Auth::attempt($credentials)) {
            return $this->respondWithError('invalidLogin', 401);
        }

        $token = Auth::user()->getRememberToken();

        return Response::json(compact('token'));
    }
}
