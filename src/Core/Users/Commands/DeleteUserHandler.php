<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\User;
use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Core\Users\Events\UserWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteUserHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @param UserRepositoryInterface $users
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * @param DeleteUser $command
     * @return User
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(DeleteUser $command)
    {
        $actor = $command->actor;
        $user = $this->users->findOrFail($command->userId, $actor);

        $user->assertCan($actor, 'delete');

        event(new UserWillBeDeleted($user, $actor, $command->data));

        $user->delete();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
