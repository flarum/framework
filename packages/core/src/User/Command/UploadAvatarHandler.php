<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\AvatarUploader;
use Flarum\User\AvatarValidator;
use Flarum\User\Event\AvatarSaving;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

    /**
     * @var AvatarUploader
     */
    protected $uploader;

    /**
     * @var \Flarum\User\AvatarValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param AvatarUploader $uploader
     * @param AvatarValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, AvatarUploader $uploader, AvatarValidator $validator)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploader = $uploader;
        $this->validator = $validator;
    }

    /**
     * @param UploadAvatar $command
     * @return \Flarum\User\User
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Flarum\Foundation\ValidationException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $this->assertCan($actor, 'edit', $user);
        }

        $this->validator->assertValid(['avatar' => $command->file]);

        $image = (new ImageManager)->make($command->file->getStream());

        $this->events->dispatch(
            new AvatarSaving($user, $actor, $image)
        );

        $this->uploader->upload($user, $image);

        $user->save();

        return $user;
    }
}
