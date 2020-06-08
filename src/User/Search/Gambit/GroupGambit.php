<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Gambit;

use Flarum\Group\Group;
use Flarum\Group\GroupRepository;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\Search\UserSearch;
use LogicException;

class GroupGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'group:(.+)';

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
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof UserSearch) {
            throw new LogicException('This gambit can only be applied on a UserSearch');
        }

        $groupIdentifiers = $this->extractGroupIdentifiers($matches);

        $groupQuery = Group::whereVisibleTo($search->getActor());

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

        $search->getQuery()->whereIn('id', $userIds, 'and', $negate);
    }

    /**
     * Extract the group names from the pattern match.
     *
     * @param array $matches
     * @return array
     */
    protected function extractGroupIdentifiers(array $matches)
    {
        return explode(',', trim($matches[1], '"'));
    }
}
