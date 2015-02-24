<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;

class EditUserCommandHandler
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
        $userToEdit = $this->users->findOrFail($command->userId, $user);

        $userToEdit->assertCan($user, 'edit');

        if (isset($command->username)) {
            $userToEdit->rename($command->username);
        }
        if (isset($command->email)) {
            $userToEdit->changeEmail($command->email);
        }
        if (isset($command->password)) {
            $userToEdit->changePassword($command->password);
        }
        if (! empty($command->readTime)) {
            $userToEdit->markAllAsRead();
        }

        event(new UserWillBeSaved($userToEdit, $command));

        $userToEdit->save();
        $this->dispatchEventsFor($userToEdit);

        return $userToEdit;
    }
}
