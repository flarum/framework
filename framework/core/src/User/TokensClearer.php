<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

class TokensClearer
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            PasswordChanged::class => 'clearPasswordTokens',
        ];
    }

    /**
     * @param PasswordChanged $event
     */
    public function clearPasswordTokens($event): void
    {
        PasswordToken::query()->where('user_id', $event->user->id)->delete();
    }
}
