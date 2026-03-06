<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class NicknameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        $name = $user->nickname ? $user->nickname : $user->username;

        // Strip characters used in markdown/HTML link syntax to prevent email
        // clients from rendering display names as hyperlinks. This also covers
        // nicknames stored before the save-time validation rule was introduced.
        $name = str_replace(['[', ']', '(', ')', '<', '>'], '', $name);

        // Insert a zero-width space after every dot to prevent email clients
        // from auto-linking dotted strings as domain names (e.g. nasty.com).
        // The zero-width space is invisible in all rendering contexts.
        return str_replace('.', ".\u{200B}", $name);
    }
}
