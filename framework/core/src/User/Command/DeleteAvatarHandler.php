<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\AvatarUploader;
use Flarum\User\Event\AvatarDeleting;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteAvatarHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected UserRepository $users,
        protected AvatarUploader $uploader
    ) {
    }

    public function handle(DeleteAvatar $command): User
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $actor->assertCan('edit', $user);
        }

        $this->uploader->remove($user);

        $this->events->dispatch(
            new AvatarDeleting($user, $actor)
        );

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
