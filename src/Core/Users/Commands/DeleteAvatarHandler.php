<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\Events\AvatarWillBeDeleted;
use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Core\Support\DispatchesEvents;
use League\Flysystem\FilesystemInterface;

class DeleteAvatarHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @param UserRepositoryInterface $users
     * @param FilesystemInterface $uploadDir
     */
    public function __construct(UserRepositoryInterface $users, FilesystemInterface $uploadDir)
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
        $user->assertCan($actor, 'edit');

        $avatarPath = $user->avatar_path;
        $user->changeAvatarPath(null);

        event(new AvatarWillBeDeleted($user, $actor));

        $this->uploadDir->delete($avatarPath);

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
