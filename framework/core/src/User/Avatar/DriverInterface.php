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

    /**
     * Return a srcset string for a user's avatar, or null if not supported.
     *
     * Example return value: "avatar.webp 1x, avatar@2x.webp 2x, avatar@3x.webp 3x"
     *
     * Third-party drivers may override this to construct srcset strings using
     * their provider's own sizing capabilities (e.g. Gravatar's ?s= parameter).
     */
    public function avatarSrcset(User $user): ?string;
}
