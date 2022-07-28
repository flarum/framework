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
        $events->listen(PasswordChanged::class, [$this, 'clearPasswordTokens']);
        $events->listen(PasswordChanged::class, [$this, 'clearEmailTokens']);
        $events->listen(EmailChanged::class, [$this, 'clearPasswordTokens']);
    }

    /**
     * @param PasswordChanged $event
     */
    public function clearPasswordTokens($event): void
    {
        PasswordToken::query()->where('user_id', $event->user->id)->delete();
    }

    /**
     * @param PasswordChanged $event
     */
    public function clearEmailTokens($event): void
    {
        EmailToken::query()->where('user_id', $event->user->id)->delete();
    }
}
