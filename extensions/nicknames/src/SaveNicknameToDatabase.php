<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Saving;
use Illuminate\Support\Arr;

class SaveNicknameToDatabase
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(Saving $event): void
    {
        $user = $event->user;
        $data = $event->data;
        $actor = $event->actor;
        $attributes = Arr::get($data, 'attributes', []);

        if (isset($attributes['nickname'])) {
            $actor->assertCan('editNickname', $user);

            $nickname = $attributes['nickname'];

            // If the user sets their nickname back to the username
            // set the nickname to null so that it just falls back to the username
            $user->nickname = $user->username === $nickname ? null : $nickname;
        }
    }
}
