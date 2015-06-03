<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Commands\ConfirmEmailCommand;
use Flarum\Core\Commands\GenerateAccessTokenCommand;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfirmAction extends BaseAction
{
    use WritesRememberCookie;

    public function handle(Request $request, $routeParams = [])
    {
        try {
            $userId = array_get($routeParams, 'id');
            $token = array_get($routeParams, 'token');
            $command = new ConfirmEmailCommand($userId, $token);
            $user = $this->dispatch($command);
        } catch (InvalidConfirmationTokenException $e) {
            return 'Invalid confirmation token';
        }

        $token = $this->dispatch(new GenerateAccessTokenCommand($user->id));

        return $this->withRememberCookie(
            $this->redirectTo(''),
            $token->id
        );
        // TODO: ->with('alert', ['type' => 'success', 'message' => 'Thanks for confirming!']);
    }
}
