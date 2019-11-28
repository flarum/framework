<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Gambit;

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

        $groupNames = $this->extractGroupNames($matches);

        // TODO: Use a JOIN instead (and don't forget to remove the findByName() method again)
        $ids = [];
        foreach ($groupNames as $name) {
            $group = $this->groups->findByName($name);
            if ($group && count($group->users)) {
                $ids = array_merge($ids, $group->users->pluck('id')->all());
            }
        }

        $search->getQuery()->whereIn('id', $ids, 'and', $negate);
    }

    /**
     * Extract the group names from the pattern match.
     *
     * @param array $matches
     * @return array
     */
    protected function extractGroupNames(array $matches)
    {
        return explode(',', trim($matches[1], '"'));
    }
}
