<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Commands\DeleteAvatarCommand;
use Flarum\Core\Events\AvatarWillBeDeleted;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Support\DispatchesEvents;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;

class DeleteAvatarCommandHandler
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

    public function __construct(UserRepositoryInterface $users, FilesystemInterface $uploadDir)
    {
        $this->users = $users;
        $this->uploadDir = $uploadDir;
    }

    public function handle(DeleteAvatarCommand $command)
    {
        $user = $this->users->findOrFail($command->userId);

        // Make sure the current user is allowed to edit the user profile.
        // This will let admins and the user themselves pass through, and
        // throw an exception otherwise.
        $user->assertCan($command->actor, 'edit');

        $avatarPath = $user->avatar_path;
        $user->changeAvatarPath(null);

        event(new AvatarWillBeDeleted($user, $command));

        $this->uploadDir->delete($avatarPath);

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
