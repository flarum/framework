<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\UserRepository;
use Flarum\Core\Users\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Flarum\Core\Users\EmailToken;

class ConfirmEmailHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Flarum\Core\Users\User
     * @throws InvalidConfirmationTokenException
     */
    public function handle(ConfirmEmail $command)
    {
        $token = EmailToken::find($command->token);

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
