<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\UserRepository;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

class DeleteUserCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function handle($command)
    {
        $user = $command->user;
        $userToDelete = $this->userRepo->findOrFail($command->userId, $user);

        $userToDelete->assertCan($user, 'delete');

        Event::fire('Flarum.Core.Users.Commands.DeleteUser.UserWillBeDeleted', [$userToDelete, $command]);

        $this->userRepo->delete($userToDelete);
        $this->dispatchEventsFor($userToDelete);

        return $userToDelete;
    }
}
