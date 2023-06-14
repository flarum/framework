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

class AvatarUploader
{
    protected Filesystem $uploadDir;

    public function __construct(Factory $filesystemFactory)
    {
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
    }

    public function upload(User $user, Image $image): void
    {
        if (extension_loaded('exif')) {
            $image->orientate();
        }

        $encodedImage = $image->fit(100, 100)->encode('png');

        $avatarPath = Str::random().'.png';

        $this->removeFileAfterSave($user);
        $user->changeAvatarPath($avatarPath);

        $this->uploadDir->put($avatarPath, $encodedImage);
    }

    /**
     * Handle the removal of the old avatar file after a successful user save
     * We don't place this in remove() because otherwise we would call changeAvatarPath 2 times when uploading.
     */
    protected function removeFileAfterSave(User $user): void
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

    public function remove(User $user): void
    {
        $this->removeFileAfterSave($user);

        $user->changeAvatarPath(null);
    }
}
