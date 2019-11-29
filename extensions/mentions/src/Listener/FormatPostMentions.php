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

class FormatPostMentions
{
    public function handle(Rendering $event)
    {
        $post = $event->context;

        $event->xml = Utils::replaceAttributes($event->xml, 'POSTMENTION', function ($attributes) use ($post) {
            $post = $post->mentionsPosts->find($attributes['id']);
            if ($post && $post->user) {
                $attributes['displayname'] = $post->user->display_name;
            }

            return $attributes;
        });
    }
}
