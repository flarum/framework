<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Users\PasswordToken;
use Flarum\Support\HtmlAction;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordAction extends HtmlAction
{
    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Illuminate\Contracts\View\View
     */
    public function render(Request $request, array $routeParams = [])
    {
        $token = array_get($routeParams, 'token');

        $token = PasswordToken::findOrFail($token);

        return view('flarum::reset')->with('token', $token->id);
    }
}
