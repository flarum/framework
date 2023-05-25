<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search;

use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class UserSearcher extends AbstractSearcher
{
    public function __construct(
        protected UserRepository $users,
        protected Dispatcher $events,
        GambitManager $gambits,
        array $searchMutators
    ) {
        parent::__construct($gambits, $searchMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }
}
