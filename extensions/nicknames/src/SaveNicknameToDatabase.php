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
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Saving $event)
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
            if ($user->username === $nickname) {
                $user->nickname = null;
            } else {
                $user->nickname = $nickname;
            }
        }
    }
}
