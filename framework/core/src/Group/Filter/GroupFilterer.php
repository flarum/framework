<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Filter;

use Flarum\Filter\AbstractFilterer;
use Flarum\Group\GroupRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupFilterer extends AbstractFilterer
{
    public function __construct(
        protected GroupRepository $groups,
        array $filters,
        array $filterMutators
    ) {
        parent::__construct($filters, $filterMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->groups->query()->whereVisibleTo($actor);
    }
}
