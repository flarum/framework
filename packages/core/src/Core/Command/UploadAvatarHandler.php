<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\AvatarWillBeSaved;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param FilesystemInterface $uploadDir
     */
    public function __construct(Dispatcher $events, UserRepository $users, FilesystemInterface $uploadDir)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @param UploadAvatar $command
     * @return \Flarum\Core\User
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $this->assertCan($actor, 'edit', $user);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'avatar');
        $command->file->moveTo($tmpFile);

        $manager = new ImageManager;
        $manager->make($tmpFile)->fit(100, 100)->save();

        $this->events->fire(
            new AvatarWillBeSaved($user, $actor, $tmpFile)
        );

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

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
