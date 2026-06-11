<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Integration;

use Flarum\Audit\AuditLogger;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

/**
 * flarum/tags admin (tag CRUD) integration.
 *
 * Stateful: hooks the Tag model lifecycle and filters out metadata-only updates. Wired
 * through the audit extender's `using()` escape hatch.
 */
class TagsAdminIntegration
{
    /**
     * @var string[]
     */
    public static $actions = ['tag.created', 'tag.updated', 'tag.deleted'];

    public function __invoke(Container $container): void
    {
        if (!class_exists(Tag::class)) {
            return;
        }

        Tag::created(function (Tag $tag) {
            AuditLogger::log('tag.created', [
                'tag_id' => $tag->id,
            ]);
        });

        Tag::updated(function (Tag $tag) {
            // If only the following properties were edited, this means we were in UpdateTagMetadata
            // and we don't want to log that.
            if (count(Arr::except($tag->getChanges(), [
                'discussion_count',
                'last_posted_at',
                'last_posted_discussion_id',
                'last_posted_user_id',
                'post_count', // Added by askvortsov/flarum-categories extension
            ])) === 0) {
                return;
            }

            AuditLogger::log('tag.updated', [
                'tag_id' => $tag->id,
            ]);
        });

        Tag::deleted(function (Tag $tag) {
            AuditLogger::log('tag.deleted', [
                'tag_id' => $tag->id,
            ]);
        });
    }
}
