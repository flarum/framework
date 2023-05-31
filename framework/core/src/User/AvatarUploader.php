<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class AvatarUploader
{
    /**
     * @var Filesystem
     */
    protected $uploadDir;

    public function __construct(Factory $filesystemFactory, protected ImageManager $imageManager)
    {
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
    }

    /**
     * @param User $user
     * @param Image $uploadedImage
     */
    public function upload(User $user, Image $uploadedImage)
    {
        if (extension_loaded('exif')) {
            $uploadedImage->orientate();
        }

        $image = $this->imageManager->make($uploadedImage);

        

        $encodedImage = $image->fitDown(100, 100)->toPng();

        $avatarPath = Str::random().'.png';

        $this->removeFileAfterSave($user);
        $user->changeAvatarPath($avatarPath);

        $this->uploadDir->put($avatarPath, $encodedImage);
    }

    /**
     * Handle the removal of the old avatar file after a successful user save
     * We don't place this in remove() because otherwise we would call changeAvatarPath 2 times when uploading.
     * @param User $user
     */
    protected function removeFileAfterSave(User $user)
    {
        $avatarPath = $user->getRawOriginal('avatar_url');

        // If there was no avatar, there's nothing to remove.
        if (! $avatarPath) {
            return;
        }

        $user->afterSave(function () use ($avatarPath) {
            if ($this->uploadDir->exists($avatarPath)) {
                $this->uploadDir->delete($avatarPath);
            }
        });
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        $this->removeFileAfterSave($user);

        $user->changeAvatarPath(null);
    }
}
