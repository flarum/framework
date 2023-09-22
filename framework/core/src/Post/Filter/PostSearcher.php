<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Post\PostRepository;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterManager;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class PostSearcher extends AbstractSearcher
{
    public function __construct(
        protected PostRepository $posts,
        FilterManager $filters,
        array $mutators
    ) {
        parent::__construct($filters, $mutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->posts->query()->select('posts.*')->whereVisibleTo($actor);
    }
}
