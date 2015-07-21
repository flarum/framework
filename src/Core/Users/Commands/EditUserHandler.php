<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\User;
use Flarum\Core\Users\UserRepository;
use Flarum\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditUserHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param EditUser $command
     * @return User
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(EditUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $user = $this->users->findOrFail($command->userId, $actor);

        $user->assertCan($actor, 'edit');

        $attributes = array_get($data, 'attributes', []);

        if (isset($attributes['username'])) {
            $user->assertCan($actor, 'rename');
            $user->rename($attributes['username']);
        }

        if (isset($attributes['email'])) {
            $user->requestEmailChange($attributes['email']);
        }

        if (isset($attributes['password'])) {
            $user->changePassword($attributes['password']);
        }

        if (isset($attributes['bio'])) {
            $user->changeBio($attributes['bio']);
        }

        if (! empty($attributes['readTime'])) {
            $user->markAllAsRead();
        }

        if (! empty($attributes['preferences'])) {
            foreach ($attributes['preferences'] as $k => $v) {
                $user->setPreference($k, $v);
            }
        }

        event(new UserWillBeSaved($actor, $actor, $data));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
