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
    public function __construct(
        protected PostRepository $posts,
        array $filters,
        array $filterMutators
    ) {
        parent::__construct($filters, $filterMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->posts->query()->select('posts.*')->whereVisibleTo($actor);
    }
}
