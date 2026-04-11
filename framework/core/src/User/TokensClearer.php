<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Http\AccessToken;
use Flarum\User\Event\EmailChanged;
use Flarum\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

class TokensClearer
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([PasswordChanged::class, EmailChanged::class], $this->clearPasswordTokens(...));
        $events->listen(PasswordChanged::class, $this->clearEmailTokens(...));
        $events->listen(PasswordChanged::class, $this->clearAccessTokens(...));
    }

    public function clearPasswordTokens(EmailChanged|PasswordChanged $event): void
    {
        $event->user->passwordTokens()->delete();
    }

    public function clearEmailTokens(PasswordChanged $event): void
    {
        $event->user->emailTokens()->delete();
    }

    public function clearAccessTokens(PasswordChanged $event): void
    {
        AccessToken::query()->where('user_id', $event->user->id)->delete();
    }
}
