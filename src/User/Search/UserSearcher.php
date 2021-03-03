<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search;

use Flarum\Query\QueryCriteria;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\Search\SearchState;
use Flarum\User\Event\Searching;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class UserSearcher extends AbstractSearcher
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     * @param Dispatcher $events
     * @param GambitManager $gambits
     * @param array $searchMutators
     */
    public function __construct(UserRepository $users, Dispatcher $events, GambitManager $gambits, array $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->events = $events;
        $this->users = $users;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }

    /**
     * @deprecated along with the Searching event, remove in Beta 17.
     */
    protected function mutateSearch(SearchState $search, QueryCriteria $criteria)
    {
        parent::mutateSearch($search, $criteria);

        $this->events->dispatch(new Searching($search, $criteria));
    }
}
