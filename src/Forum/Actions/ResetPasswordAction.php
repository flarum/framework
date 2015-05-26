<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\ResetToken;
use Illuminate\Http\Request;

class ResetPasswordAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $token = array_get($routeParams, 'token');

        $token = ResetToken::findOrFail($token);

        return view('flarum::reset')->with('token', $token->id);
    }
}
