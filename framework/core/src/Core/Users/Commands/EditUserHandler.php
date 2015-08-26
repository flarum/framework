<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\User;
use Flarum\Core\Users\UserRepository;
use Flarum\Events\UserWillBeSaved;
use Flarum\Events\UserGroupsWereChanged;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Exceptions\PermissionDeniedException;

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
        $isSelf = $actor->id === $user->id;

        $attributes = array_get($data, 'attributes', []);
        $relationships = array_get($data, 'relationships', []);

        if (isset($attributes['username'])) {
            $user->assertCan($actor, 'edit');
            $user->rename($attributes['username']);
        }

        if (isset($attributes['email'])) {
            if ($isSelf) {
                $user->requestEmailChange($attributes['email']);
            } else {
                $user->assertCan($actor, 'edit');
                $user->changeEmail($attributes['email']);
            }
        }

        if (isset($attributes['password'])) {
            $user->assertCan($actor, 'edit');
            $user->changePassword($attributes['password']);
        }

        if (isset($attributes['bio'])) {
            if (! $isSelf) {
                $user->assertCan($actor, 'edit');
            }

            $user->changeBio($attributes['bio']);
        }

        if (! empty($attributes['readTime'])) {
            $this->assert($isSelf);
            $user->markAllAsRead();
        }

        if (! empty($attributes['preferences'])) {
            $this->assert($isSelf);

            foreach ($attributes['preferences'] as $k => $v) {
                $user->setPreference($k, $v);
            }
        }

        if (isset($relationships['groups']['data']) && is_array($relationships['groups']['data'])) {
            $user->assertCan($actor, 'edit');

            $newGroupIds = [];
            foreach ($relationships['groups']['data'] as $group) {
                if ($id = array_get($group, 'id')) {
                    $newGroupIds[] = $id;
                }
            }

            $user->raise(new UserGroupsWereChanged($user, $user->groups()->get()->all()));

            User::saved(function ($user) use ($newGroupIds) {
                $user->groups()->sync($newGroupIds);
            });
        }

        event(new UserWillBeSaved($user, $actor, $data));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }

    protected function assert($true)
    {
        if (! $true) {
            throw new PermissionDeniedException;
        }
    }
}
