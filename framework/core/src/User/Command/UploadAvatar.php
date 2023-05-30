<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\User\User;
use Psr\Http\Message\UploadedFileInterface;

class UploadAvatar
{
    public function __construct(
        public int $userId,
        public UploadedFileInterface $file,
        public User $actor
    ) {
    }
}
