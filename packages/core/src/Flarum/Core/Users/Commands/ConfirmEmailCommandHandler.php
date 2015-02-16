<?php namespace Flarum\Core\Users\Commands;

use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Users\UserRepository;

class ConfirmEmailCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function handle($command)
    {
        $user = $this->userRepo->findOrFail($command->userId);

        $user->confirmEmail($command->token);

        // If the user hasn't yet had their account activated,
        if (! $user->join_time) {
            $user->activate();
        }

        Event::fire('Flarum.Core.Users.Commands.ConfirmEmail.UserWillBeSaved', [$user, $command]);

        $this->userRepo->save($user);
        $this->dispatchEventsFor($user);

        return $user;
    }
}
