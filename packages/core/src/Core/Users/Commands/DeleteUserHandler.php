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
use Flarum\Events\UserWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteUserHandler
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
