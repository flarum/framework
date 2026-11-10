<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Event;

use Flarum\User\User;

/**
 * Every application a member had connected was disconnected at once: by the
 * member from their settings, or by logging out everywhere or a change of
 * their password (made by them, by a password reset or by an admin).
 */
class DisconnectedAll
{
    /** The member disconnected them from their settings. */
    public const string SETTINGS = 'settings';

    /** The member logged out everywhere. */
    public const string LOGOUT_EVERYWHERE = 'logout_everywhere';

    /** The member's password was changed. */
    public const string PASSWORD_CHANGE = 'password_change';

    /**
     * @param self::SETTINGS|self::LOGOUT_EVERYWHERE|self::PASSWORD_CHANGE $cause
     */
    public function __construct(
        public User $user,
        public string $cause,
    ) {
    }
}
