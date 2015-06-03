<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\ResetToken;
use Flarum\Core\Commands\EditUserCommand;
use Psr\Http\Message\ServerRequestInterface as Request;

class SavePasswordAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $token = ResetToken::findOrFail($request->getAttribute('token'));

        $password = $request->getAttribute('password');
        $confirmation = $request->getAttribute('password_confirmation');

        if (! $password || $password !== $confirmation) {
            return $this->redirectTo(''); // TODO: Redirect back
        }

        $this->dispatch(
            new EditUserCommand($token->user_id, $token->user, ['password' => $password])
        );

        $token->delete();

        return $this->redirectTo('');
    }
}
