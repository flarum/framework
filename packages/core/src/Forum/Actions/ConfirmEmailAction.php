<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Flarum\Core\Commands\ConfirmEmailCommand;
use Flarum\Core\Commands\GenerateAccessTokenCommand;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;

class ConfirmEmailAction extends BaseAction
{
    use MakesRememberCookie;

    public function handle(Request $request, $routeParams = [])
    {
        try {
            $token = array_get($routeParams, 'token');
            $command = new ConfirmEmailCommand($token);
            $user = $this->dispatch($command);
        } catch (InvalidConfirmationTokenException $e) {
            return 'Invalid confirmation token';
        }

        $command = new GenerateAccessTokenCommand($user->id);
        $token = $this->dispatch($command);

        return redirect('/')
            ->withCookie($this->makeRememberCookie($token->id));
    }
}
