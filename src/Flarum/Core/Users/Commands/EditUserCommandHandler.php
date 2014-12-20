<?php namespace Flarum\Core\Users\Commands;

use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Users\UserRepository;

class EditUserCommandHandler implements CommandHandler
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
        $userToEdit = $this->userRepo->findOrFail($command->userId, $user);

        $userToEdit->assertCan($user, 'edit');

        if (isset($command->username)) {
            $userToEdit->username = $command->username;
        }

        if (isset($command->email)) {
            $userToEdit->email = $command->email;
        }

        if (isset($command->password)) {
            $userToEdit->password = $command->password;
        }

        Event::fire('Flarum.Core.Users.Commands.EditUser.UserWillBeSaved', [$userToEdit, $command]);

        $this->userRepo->save($userToEdit);
        $this->dispatchEventsFor($userToEdit);

        return $userToEdit;
    }
}
