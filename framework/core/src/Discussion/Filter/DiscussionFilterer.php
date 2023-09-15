<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Filter\AbstractFilterer;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionFilterer extends AbstractFilterer
{
    public function __construct(protected DiscussionRepository $discussions, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->discussions->query()->select('discussions.*')->whereVisibleTo($actor);
    }
}
