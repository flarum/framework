<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search;

use Flarum\Search\AbstractSearch;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\Search\SearchCriteria;
use Flarum\User\Event\Searching;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Takes a UserSearchCriteria object, performs a search using gambits,
 * and spits out a UserSearchResults object.
 */
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

    protected function getSearch(Builder $query, User $actor): AbstractSearch
    {
        return new UserSearch($query->getQuery(), $actor);
    }

    /**
     * @deprecated along with the Searching event, remove in Beta 17.
     */
    protected function mutateSearch(AbstractSearch $search, SearchCriteria $criteria)
    {
        parent::mutateSearch($search, $criteria);

        $this->events->dispatch(new Searching($search, $criteria));
    }
}
