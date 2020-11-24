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
        $canEdit = $actor->can('edit', $user);
        $attributes = Arr::get($data, 'attributes', []);

        if (isset($attributes['nickname'])) {
            if (!$isSelf) {
                $actor->assertPermission($canEdit);
            }
            $user->nickname = $attributes['nickname'];
            $user->save();
        }
    }
}
