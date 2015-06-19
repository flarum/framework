<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\PasswordToken;
use Flarum\Core\Commands\EditUserCommand;
use Psr\Http\Message\ServerRequestInterface as Request;

class SavePasswordAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(array_get($input, 'token'));

        $password = array_get($input, 'password');
        $confirmation = array_get($input, 'password_confirmation');

        if (! $password || $password !== $confirmation) {
            return $this->redirectTo('/reset/'.$token->id); // TODO: Use UrlGenerator
        }

        $this->dispatch(
            new EditUserCommand($token->user_id, $token->user, ['password' => $password])
        );

        $token->delete();

        return $this->redirectTo('/');
    }
}
