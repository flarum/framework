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
use Flarum\Query\QueryCriteria;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\Search\SearchState;
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
     * @param array $searchMutators
     */
    public function __construct(DiscussionRepository $discussions, Dispatcher $events, GambitManager $gambits, array $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->discussions = $discussions;
        $this->events = $events;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);
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
