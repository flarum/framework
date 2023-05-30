<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\Event\Deleting;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteUserHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected UserRepository $users
    ) {
    }

    /**
     * @throws PermissionDeniedException
     */
    public function handle(DeleteUser $command): User
    {
        $actor = $command->actor;
        $user = $this->users->findOrFail($command->userId, $actor);

        $actor->assertCan('delete', $user);

        $this->events->dispatch(
            new Deleting($user, $actor, $command->data)
        );

        $user->delete();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
