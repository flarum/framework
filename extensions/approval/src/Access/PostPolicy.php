<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\Discussion\Discussion;
use Flarum\Event\ScopeModelVisibility;
use Flarum\Post\Post;
use Flarum\User\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class PostPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Post::class;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * @param Builder $query
     * @param User $actor
     */
    public function findPrivate(User $actor, Builder $query)
    {
        // Show private posts if they require approval and they are
        // authored by the current user, or the current user has permission to
        // approve posts.
        $query->where('posts.is_approved', 0);

        if (! $actor->hasPermission('discussion.approvePosts')) {
            $query->where(function (Builder $query) use ($actor) {
                $query->where('posts.user_id', $actor->id)
                    ->orWhereExists($this->discussionWhereCanApprovePosts($actor));
            });
        }
    }

    private function discussionWhereCanApprovePosts(User $actor)
    {
        return function ($query) use ($actor) {
            $query->selectRaw('1')
                ->from('discussions')
                ->whereColumn('discussions.id', 'posts.discussion_id')
                ->where($this->canApprovePosts($actor));
        };
    }

    private function canApprovePosts(User $actor)
    {
        return function ($query) use ($actor) {
            $this->events->dispatch(
                new ScopeModelVisibility(Discussion::query()->setQuery($query), $actor, 'approvePosts')
            );
        };
    }
}
