<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\DisplayName;

use Flarum\User\User;

/**
 * An interface for a display name driver.
 *
 * @public
 */
interface DriverInterface
{
    /**
     * Return a display name for a user.
     */
    public function displayName(User $user): string;
}
