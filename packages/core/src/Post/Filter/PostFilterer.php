<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Filter\AbstractFilterer;
use Flarum\Post\PostRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class PostFilterer extends AbstractFilterer
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(PostRepository $posts, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->posts = $posts;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->posts->query()->whereVisibleTo($actor);
    }
}
