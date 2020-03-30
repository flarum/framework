<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Search;

use Flarum\Post\PostRepository;
use Flarum\Search\AbstractSearch;
use Flarum\Search\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class PostSearcher extends AbstractSearcher
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param PostRepository $posts
     * @param Dispatcher $events
     */
    public function __construct(PostRepository $posts, Dispatcher $events)
    {
        $this->posts = $posts;
        $this->events = $events;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->posts->query()->select('posts.*')->whereVisibleTo($actor);
    }

    protected function getSearch(Builder $query, User $actor): AbstractSearch
    {
        return new PostSearch($query->getQuery(), $actor);
    }
}
