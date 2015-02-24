<?php namespace Flarum\Web\Actions;

use Flarum\Core\Users\Commands\ConfirmEmailCommand;
use Cookie;

class ConfirmAction extends Action
{
    use MakesRememberCookie;

    public function respond(Request $request, $params = [])
    {
        try {
            $command = new ConfirmEmailCommand($userId, $token);
            $user = $this->dispatch($command);
        } catch (InvalidConfirmationTokenException $e) {
            return 'Invalid confirmation token';
        }

        $token = AccessToken::generate($user->id);
        $token->save();

        return Redirect::to('/')
            ->withCookie($this->makeRememberCookie($token->id))
            ->with('alert', ['type' => 'success', 'message' => 'Thanks for confirming!']);
    }
}
