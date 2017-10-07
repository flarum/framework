<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Intervention\Image\Image;
use League\Flysystem\FilesystemInterface;
use Illuminate\Support\Str;

class AvatarUploader
{
    protected $uploadDir;

    public function __construct(FilesystemInterface $uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    public function upload(User $user, Image $image)
    {
        if (extension_loaded('exif')) {
            $image->orientate();
        }

        $encodedImage = $image->fit(100, 100)->encode('png');

        $avatarPath = Str::quickRandom().'.png';

        $this->remove($user);
        $user->changeAvatarPath($avatarPath);

        $this->uploadDir->put($avatarPath, $encodedImage);
    }

    public function remove(User $user)
    {
        $avatarPath = $user->avatar_path;

        $user->afterSave(function () use ($avatarPath) {
            if ($this->uploadDir->has($avatarPath)) {
                $this->uploadDir->delete($avatarPath);
            }
        });

        $user->changeAvatarPath(null);
    }
}
