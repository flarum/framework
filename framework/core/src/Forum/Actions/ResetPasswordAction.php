<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\PasswordToken;
use Illuminate\Http\Request;

class ResetPasswordAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $token = array_get($routeParams, 'token');

        $token = PasswordToken::findOrFail($token);

        return view('flarum::reset')->with('token', $token->id);
    }
}
