<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Commands\UploadAvatarCommand;
use Flarum\Core\Events\AvatarWillBeUploaded;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Support\DispatchesEvents;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Intervention\Image\ImageManager;

class UploadAvatarCommandHandler
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

    public function handle(UploadAvatarCommand $command)
    {
        $user = $this->users->findOrFail($command->userId);

        // Make sure the current user is allowed to edit the user profile.
        // This will let admins and the user themselves pass through, and
        // throw an exception otherwise.
        $user->assertCan($command->actor, 'edit');

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar');
        $command->file->moveTo($tmpFile);

        $uploadName = Str::lower(Str::quickRandom()) . '.jpg';

        $manager = new ImageManager(array('driver' => 'imagick'));
        $manager->make($tmpFile)->fit(100, 100)->save();

        $mount = new MountManager([
            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
            'target' => $this->uploadDir,
        ]);

        if ($user->avatar_path && $mount->has($file = "target://$user->avatar_path")) {
            $mount->delete($file);
        }

        $user->changeAvatarPath($uploadName);

        event(new AvatarWillBeUploaded($user, $command));

        $mount->move("source://".pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
