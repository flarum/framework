<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Avatar;

use Flarum\User\User;

/**
 * An interface for an avatar driver.
 *
 * @public
 */
interface DriverInterface
{
    /**
     * Return an avatar URL for a user.
     */
    public function avatarUrl(User $user): ?string;
}
