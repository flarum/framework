<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Search;

use Flarum\Group\GroupRepository;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class GroupSearcher extends AbstractSearcher
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     * @param Dispatcher $events
     * @param GambitManager $gambits
     * @param array $searchMutators
     */
    public function __construct(GroupRepository $groups, Dispatcher $events, GambitManager $gambits, array $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->events = $events;
        $this->groups = $groups;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->groups->query()->whereVisibleTo($actor);
    }
}
