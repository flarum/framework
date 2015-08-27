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

use Flarum\Events\AvatarWillBeSaved;
use Flarum\Core\Users\UserRepository;
use Flarum\Core\Support\DispatchesEvents;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
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
     * @param UploadAvatar $command
     * @return \Flarum\Core\Users\User
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        // Make sure the current user is allowed to edit the user profile.
        // This will let admins and the user themselves pass through, and
        // throw an exception otherwise.
        if ($actor->id !== $user->id) {
            $user->assertCan($actor, 'edit');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar');
        $command->file->moveTo($tmpFile);

        $manager = new ImageManager();
        $manager->make($tmpFile)->fit(100, 100)->save();

        event(new AvatarWillBeSaved($user, $actor, $tmpFile));

        $mount = new MountManager([
            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
            'target' => $this->uploadDir,
        ]);

        if ($user->avatar_path && $mount->has($file = "target://$user->avatar_path")) {
            $mount->delete($file);
        }

        $uploadName = Str::lower(Str::quickRandom()) . '.jpg';

        $user->changeAvatarPath($uploadName);

        $mount->move("source://".pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
