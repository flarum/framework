<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Events\UserWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteUserCommandHandler
{
    use DispatchesEvents;

    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function handle($command)
    {
        $user = $command->user;
        $userToDelete = $this->users->findOrFail($command->userId, $user);

        $userToDelete->assertCan($user, 'delete');

        event(new UserWillBeDeleted($userToDelete, $command));

        $userToDelete->delete();
        $this->dispatchEventsFor($userToDelete);

        return $userToDelete;
    }
}
