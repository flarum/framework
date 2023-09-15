<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class DiscussionSearcher extends AbstractSearcher
{
    public function __construct(
        protected DiscussionRepository $discussions,
        protected Dispatcher $events,
        GambitManager $gambits,
        array $searchMutators
    ) {
        parent::__construct($gambits, $searchMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);
    }
}
