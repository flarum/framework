<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\User\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class GroupPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Group::class;

    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return true;
        }
    }

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        if ($actor->cannot('viewHiddenGroups')) {
            $query->where('is_hidden', false);
        }
    }
}
