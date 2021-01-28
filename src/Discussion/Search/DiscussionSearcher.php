<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Discussion\Event\Searching;
use Flarum\Search\AbstractSearch;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchMutators;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class DiscussionSearcher extends AbstractSearcher
{
    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param DiscussionRepository $discussions
     * @param Dispatcher $events
     * @param GambitManager $gambits
     * @param SearchMutators $searchMutators
     */
    public function __construct(DiscussionRepository $discussions, Dispatcher $events, GambitManager $gambits, SearchMutators $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->discussions = $discussions;
        $this->events = $events;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);
    }

    protected function getSearch(Builder $query, User $actor): AbstractSearch
    {
        return new DiscussionSearch($query->getQuery(), $actor);
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
