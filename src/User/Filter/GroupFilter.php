<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;
use Flarum\Group\Group;

class GroupFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'group';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $groupIdentifiers = explode(',', trim($filterValue, '"'));

        $groupQuery = Group::whereVisibleTo($wrappedFilter->getActor());

        foreach ($groupIdentifiers as $identifier) {
            if (is_numeric($identifier)) {
                $groupQuery->orWhere('id', $identifier);
            } else {
                $groupQuery->orWhere('name_singular', $identifier)->orWhere('name_plural', $identifier);
            }
        }

        $userIds = $groupQuery->join('group_user', 'groups.id', 'group_user.group_id')
        ->pluck('group_user.user_id')
        ->all();

        $wrappedFilter->getQuery()->whereIn('id', $userIds, 'and', $negate);
    }
}
