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
use Flarum\Search\ApplySearchParametersTrait;
use Flarum\Search\GambitManager;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchResults;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionSearcher
{
    use ApplySearchParametersTrait;

    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param GambitManager $gambits
     * @param DiscussionRepository $discussions
     * @param Dispatcher $events
     */
    public function __construct(GambitManager $gambits, DiscussionRepository $discussions, Dispatcher $events)
    {
        $this->gambits = $gambits;
        $this->discussions = $discussions;
        $this->events = $events;
    }

    /**
     * @param SearchCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     *
     * @return SearchResults
     */
    public function search(SearchCriteria $criteria, $limit = null, $offset = 0)
    {
        $actor = $criteria->actor;

        $query = $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);

        // Construct an object which represents this search for discussions.
        // Apply gambits to it, sort, and paging criteria. Also give extensions
        // an opportunity to modify it.
        $search = new DiscussionSearch($query->getQuery(), $actor);

        $this->gambits->apply($search, $criteria->query);
        $this->applySort($search, $criteria->sort);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        $this->events->dispatch(new Searching($search, $criteria));

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $discussions = $query->get();

        $areMoreResults = $limit > 0 && $discussions->count() > $limit;

        if ($areMoreResults) {
            $discussions->pop();
        }

        return new SearchResults($discussions, $areMoreResults);
    }
}
