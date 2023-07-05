<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\AbstractFilterer;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class UserFilterer extends AbstractFilterer
{
    public function __construct(
        protected UserRepository $users,
        array $filters,
        array $filterMutators
    ) {
        parent::__construct($filters, $filterMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }
}
