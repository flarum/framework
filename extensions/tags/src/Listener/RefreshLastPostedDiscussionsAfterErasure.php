<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Gdpr\Events\Erased;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Tags\Tag;

/**
 * Erasing a user in deletion mode deletes their posts in one query, without
 * the post events that keep each tag's last posted discussion up to date.
 */
class RefreshLastPostedDiscussionsAfterErasure
{
    public function handle(Erased $event): void
    {
        if ($event->mode !== ErasureRequest::MODE_DELETION) {
            return;
        }

        Tag::query()->each(function (Tag $tag) {
            $tag->refreshLastPostedDiscussion()->save();
        });
    }
}
