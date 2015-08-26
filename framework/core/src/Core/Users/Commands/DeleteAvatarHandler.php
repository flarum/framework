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

use Flarum\Events\AvatarWillBeDeleted;
use Flarum\Core\Users\UserRepository;
use Flarum\Core\Support\DispatchesEvents;
use League\Flysystem\FilesystemInterface;

class DeleteAvatarHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @param UserRepository $users
     * @param FilesystemInterface $uploadDir
     */
    public function __construct(UserRepository $users, FilesystemInterface $uploadDir)
    {
        $this->users = $users;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @param DeleteAvatar $command
     * @return \Flarum\Core\Users\User
     */
    public function handle(DeleteAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        // Make sure the current user is allowed to edit the user profile.
        // This will let admins and the user themselves pass through, and
        // throw an exception otherwise.
        if ($actor->id !== $user->id) {
            $user->assertCan($actor, 'edit');
        }

        $avatarPath = $user->avatar_path;
        $user->changeAvatarPath(null);

        event(new AvatarWillBeDeleted($user, $actor));

        if ($this->uploadDir->has($avatarPath)) {
            $this->uploadDir->delete($avatarPath);
        }

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
