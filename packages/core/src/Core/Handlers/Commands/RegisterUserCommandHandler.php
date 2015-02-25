<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Models\User;
use Flarum\Core\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class RegisterUserCommandHandler
{
    use DispatchesEvents;

    public function handle($command)
    {
        // Assert the the current user has permission to create a user. In the
        // case of a guest trying to register an account, this will depend on
        // whether or not registration is open. If the user is an admin, though,
        // it will be allowed.
        $command->forum->assertCan($command->user, 'register');

        // Create a new User entity, persist it, and dispatch domain events.
        // Before persistance, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $user = User::register(
            $command->username,
            $command->email,
            $command->password
        );

        event(new UserWillBeSaved($user, $command));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
