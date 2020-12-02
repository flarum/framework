<?php

namespace Flarum\Nicknames;

use Flarum\User\Event\Saving;
use Illuminate\Support\Arr;

class SaveNicknameToDatabase {
    public function handle(Saving $event)
    {
        $user = $event->user;
        $data = $event->data;
        $actor = $event->actor;

        $isSelf = $actor->id === $user->id;
        $attributes = Arr::get($data, 'attributes', []);

        if (isset($attributes['nickname'])) {
            if ($isSelf) {
                $actor->assertCan('editOwnNickname', $user);
            } else {
                $actor->assertCan('edit', $user);
            }

            $nickname = $attributes['nickname'];

            // If unique validation is enabled, the nickname will be checked
            // against ALL nicknames and usernames, including the username
            // of the current user. So, to allow users to reset their nickname
            // back to their username, in this case we'd set it to null.
            if ($user->username === $nickname) {
                $user->nickname = null;
            } else {
                $user->nickname = $nickname;
            }
        }
    }
}
