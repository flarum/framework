<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Search\Gambit;

use Flarum\Group\Group;
use Flarum\Group\GroupRepository;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;

class FulltextGambit implements GambitInterface
{
    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param \Flarum\Group\GroupRepository $groups
     */
    public function __construct(GroupRepository $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getGroupSearchSubQuery($searchValue)
    {
        return $this->groups
            ->query()
            ->select('id')
            ->where(function ($query) use ($searchValue) {
                $query->where('name_plural', 'like', "{$searchValue}%")
                    ->orWhere('name_singular', 'like', "{$searchValue}%");
            })
            ->whereNotIn('id', [Group::GUEST_ID, Group::MEMBER_ID]);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $searchValue)
    {
        $search->getQuery()
            ->whereIn(
                'id',
                $this->getGroupSearchSubQuery($searchValue)
            );

        return true;
    }
}
