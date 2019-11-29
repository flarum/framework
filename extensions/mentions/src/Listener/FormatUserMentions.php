<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Formatter\Event\Rendering;
use s9e\TextFormatter\Utils;

class FormatUserMentions
{
    public function handle(Rendering $event)
    {
        $post = $event->context;

        $event->xml = Utils::replaceAttributes($event->xml, 'USERMENTION', function ($attributes) use ($post) {
            $user = $post->mentionsUsers->find($attributes['id']);
            if ($user) {
                $attributes['username'] = $user->username;
                $attributes['displayname'] = $user->display_name;
            }

            return $attributes;
        });
    }
}
