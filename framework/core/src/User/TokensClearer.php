<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\User\Event\EmailChanged;
use Flarum\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

class TokensClearer
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([PasswordChanged::class, EmailChanged::class], $this->clearPasswordTokens(...));
        $events->listen(PasswordChanged::class, $this->clearEmailTokens(...));
    }

    public function clearPasswordTokens(EmailChanged|PasswordChanged $event): void
    {
        $event->user->passwordTokens()->delete();
    }

    public function clearEmailTokens(PasswordChanged $event): void
    {
        $event->user->emailTokens()->delete();
    }
}
