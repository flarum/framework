<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Models\User;
use Flarum\Core\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class RegisterUserCommandHandler
{
    use DispatchesEvents;

    public function handle($command)
    {
        // @todo check whether or not registration is open (config)

        // Create a new User entity, persist it, and dispatch domain events.
        // Before persistance, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $user = User::register(
            array_get($command->data, 'username'),
            array_get($command->data, 'email'),
            array_get($command->data, 'password')
        );

        event(new UserWillBeSaved($user, $command));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
