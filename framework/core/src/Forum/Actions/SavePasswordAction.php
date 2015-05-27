<?php namespace Flarum\Forum\Actions;

use Flarum\Core\Models\PasswordToken;
use Flarum\Core\Commands\EditUserCommand;
use Illuminate\Http\Request;

class SavePasswordAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $token = PasswordToken::findOrFail($request->get('token'));

        $password = $request->get('password');
        $confirmation = $request->get('password_confirmation');

        if (! $password || $password !== $confirmation) {
            return redirect()->back();
        }

        $this->dispatch(
            new EditUserCommand($token->user_id, $token->user, ['password' => $password])
        );

        $token->delete();

        return redirect('');
    }
}
