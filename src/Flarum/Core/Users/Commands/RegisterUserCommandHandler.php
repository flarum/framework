<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Forum;
use Flarum\Core\Users\User;
use Flarum\Core\Users\UserRepository;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

class RegisterUserCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $forum;

    protected $userRepo;

    public function __construct(Forum $forum, UserRepository $userRepo)
    {
        $this->forum = $forum;
        $this->userRepo = $userRepo;
    }

    public function handle($command)
    {
        // Assert the the current user has permission to create a user. In the
        // case of a guest trying to register an account, this will depend on
        // whether or not registration is open. If the user is an admin, though,
        // it will be allowed.
        $this->forum->assertCan($command->user, 'register');

        // Create a new User entity, persist it, and dispatch domain events.
        // Before persistance, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $user = User::register(
            $command->username,
            $command->email,
            $command->password
        );

        Event::fire('Flarum.Core.Users.Commands.RegisterUser.UserWillBeSaved', [$user, $command]);
        
        $this->userRepo->save($user);
        $this->userRepo->syncGroups($user, [3]); // default groups
        $this->dispatchEventsFor($user);

        return $user;
    }
}
