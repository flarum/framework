<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Flarum\Core\Models\EmailToken;

class ConfirmEmailCommandHandler
{
    use DispatchesEvents;

    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function handle($command)
    {
        $token = EmailToken::find($command->token)->first();

        if (! $token) {
            throw new InvalidConfirmationTokenException;
        }

        $user = $token->user;
        $user->changeEmail($token->email);

        if (! $user->is_activated) {
            $user->activate();
        }

        event(new UserWillBeSaved($user, $command));

        $user->save();
        $this->dispatchEventsFor($user);

        $token->delete();

        return $user;
    }
}
