<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

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
        $user = $this->users->findOrFail($command->userId);

        $user->assertConfirmationTokenValid($command->token);
        $user->confirmEmail();

        if (! $user->is_activated) {
            $user->activate();
        }

        event(new UserWillBeSaved($user, $command));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
