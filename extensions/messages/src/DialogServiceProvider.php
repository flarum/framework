<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\User;

class DialogServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Formatter $formatter): void
    {
        DialogMessage::setFormatter($formatter);

        // A member who leaves doesn't take the other side of their
        // conversations with them. Their messages stay, with no author, as
        // their posts do. Done here, before the row goes, because the foreign
        // key would otherwise cascade and delete the messages outright.
        User::deleting(function (User $user) {
            DialogMessage::query()->where('user_id', $user->id)->update(['user_id' => null]);
        });
    }
}
