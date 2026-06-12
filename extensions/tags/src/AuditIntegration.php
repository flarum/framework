<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Audit\AuditLogger;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

/**
 * Audit log integration for flarum/tags admin (tag CRUD).
 *
 * Stateful: hooks the Tag model lifecycle and filters out metadata-only updates. Wired into
 * flarum/audit through the Flarum\Audit\Extend\Audit extender's `using()` escape hatch, behind
 * an Extend\Conditional so it's only active when flarum-audit is installed.
 */
class AuditIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = ['tag.created', 'tag.updated', 'tag.deleted'];

    public function __invoke(Container $container): void
    {
        // Listen on the events dispatcher rather than the static Tag::event(Closure) API, so the
        // listeners aren't bound to the model's static dispatcher.
        $events = $container->make(Dispatcher::class);

        $events->listen('eloquent.created: '.Tag::class, [$this, 'created']);
        $events->listen('eloquent.updated: '.Tag::class, [$this, 'updated']);
        $events->listen('eloquent.deleted: '.Tag::class, [$this, 'deleted']);
    }

    public function created(Tag $tag): void
    {
        AuditLogger::log('tag.created', [
            'tag_id' => $tag->id,
        ]);
    }

    public function updated(Tag $tag): void
    {
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
    }

    public function deleted(Tag $tag): void
    {
        AuditLogger::log('tag.deleted', [
            'tag_id' => $tag->id,
        ]);
    }
}
