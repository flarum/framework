<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Events\ScopeModelVisibility;
use Flarum\Events\ScopeHiddenDiscussionVisibility;
use Flarum\Events\ModelAllow;
use Flarum\Core\Discussions\Discussion;
use Flarum\Tags\Tag;
use Flarum\Reports\Report;
use Illuminate\Database\Query\Expression;

class ConfigureDiscussionPermissions
{
    public function subscribe($events)
    {
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeDiscussionVisibility']);
        $events->listen(ScopeHiddenDiscussionVisibility::class, [$this, 'scopeHiddenDiscussionVisibility']);
        $events->listen(ModelAllow::class, [$this, 'allowDiscussionPermissions']);
    }

    public function scopeDiscussionVisibility(ScopeModelVisibility $event)
    {
        // Hide discussions which have tags that the user is not allowed to see.
        if ($event->model instanceof Discussion) {
            $event->query->whereNotExists(function ($query) use ($event) {
                return $query->select(new Expression(1))
                    ->from('discussions_tags')
                    ->whereIn('tag_id', Tag::getIdsWhereCannot($event->actor, 'view'))
                    ->where('discussions.id', new Expression('discussion_id'));
            });
        }

        if ($event->model instanceof Flag) {
            $event->query
                ->select('flags.*')
                ->leftJoin('posts', 'posts.id', '=', 'flags.post_id')
                ->leftJoin('discussions', 'discussions.id', '=', 'posts.discussion_id')
                ->whereNotExists(function ($query) use ($event) {
                    return $query->select(new Expression(1))
                        ->from('discussions_tags')
                        ->whereIn('tag_id', Tag::getIdsWhereCannot($event->actor, 'discussion.viewFlags'))
                        ->where('discussions.id', new Expression('discussion_id'));
                });
        }
    }

    public function scopeHiddenDiscussionVisibility(ScopeHiddenDiscussionVisibility $event)
    {
        // By default, discussions are not visible to the public if they are
        // hidden or contain zero comments - unless the actor has a certain
        // permission. Since we grant permissions per-tag, we will make
        // discussions visible in the tags for which the user has that
        // permission.
        $event->query->orWhereExists(function ($query) use ($event) {
            return $query->select(new Expression(1))
                ->from('discussions_tags')
                ->whereIn('tag_id', Tag::getIdsWhereCan($event->actor, $event->permission))
                ->where('discussions.id', new Expression('discussion_id'));
        });
    }

    public function allowDiscussionPermissions(ModelAllow $event)
    {
        // Wrap all discussion permission checks with some logic pertaining to
        // the discussion's tags. If the discussion has a tag that has been
        // restricted, and the user has this permission for that tag, then they
        // are allowed. If the discussion only has tags that have been
        // restricted, then the user *must* have permission for at least one of
        // them.
        if ($event->model instanceof Discussion) {
            $tags = $event->model->tags;

            if (count($tags)) {
                $restricted = true;

                foreach ($tags as $tag) {
                    if ($tag->is_restricted) {
                        if ($event->actor->hasPermission('tag' . $tag->id . '.discussion.' . $event->action)) {
                            return true;
                        }
                    } else {
                        $restricted = false;
                    }
                }

                if ($restricted) {
                    return false;
                }
            }
        }
    }
}
