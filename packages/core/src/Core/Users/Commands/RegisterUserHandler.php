<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\User;
use Flarum\Core\Users\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class RegisterUserHandler
{
    use DispatchesEvents;

    /**
     * @param RegisterUser $command
     * @return User
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        // TODO: check whether or not registration is open (config)

        $user = User::register(
            array_get($data, 'attributes.username'),
            array_get($data, 'attributes.email'),
            array_get($data, 'attributes.password')
        );

        event(new UserWillBeSaved($user, $actor, $data));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
