<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\PasswordToken;
use Flarum\Support\HtmlAction;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordAction extends HtmlAction
{
    public function render(Request $request, $routeParams = [])
    {
        $token = array_get($routeParams, 'token');

        $token = PasswordToken::findOrFail($token);

        return view('flarum::reset')->with('token', $token->id);
    }
}
