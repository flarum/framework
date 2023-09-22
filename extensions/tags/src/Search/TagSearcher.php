<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search;

use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterManager;
use Flarum\Tags\TagRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class TagSearcher extends AbstractSearcher
{
    public function __construct(
        protected TagRepository $tags,
        FilterManager $filters,
        array $mutators
    ) {
        parent::__construct($filters, $mutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->tags->query()->whereVisibleTo($actor);
    }
}
