<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\Database\AbstractModel;
use Flarum\Event\GetPermission;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class Gate
{
    protected const EVALUATION_CRITERIA = [
        AbstractPolicy::FORCE_DENY => false,
        AbstractPolicy::FORCE_ALLOW => true,
        AbstractPolicy::DENY => false,
        AbstractPolicy::ALLOW => true,
    ];

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var array
     */
    protected $policies;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events, array $policies)
    {
        $this->events = $events;
        $this->policies = $policies;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  User $actor
     * @param  string  $ability
     * @param  string|AbstractModel $model
     * @return bool
     */
    public function allows(User $actor, string $ability, $model): bool
    {
        $results = [];
        $appliedPolicies = [];

        if ($model) {
            $modelClasses = is_string($model) ? [$model] : array_merge(array_reverse(class_parents(($model)), [get_class($model)]));

            foreach ($modelClasses as $class) {
                $appliedPolicies = array_merge($appliedPolicies, Arr::get($this->policies, $class, []));
            }
        } else {
            $appliedPolicies = Arr::get($this->policies, AbstractPolicy::GLOBAL);
        }

        foreach ($appliedPolicies as $policy) {
            $results[] = $policy->checkAbility($actor, $ability, $model);
        }

        foreach (static::EVALUATION_CRITERIA as $criteria => $decision) {
            if (in_array($criteria, $results, true)) {
                return $decision;
            }
        }

        // START OLD DEPRECATED SYSTEM

        // Fire an event so that core and extension modelPolicies can hook into
        // this permission query and explicitly grant or deny the
        // permission.
        $allowed = $this->events->until(
            new GetPermission($actor, $ability, $model)
        );

        if (! is_null($allowed)) {
            return $allowed;
        }
        // END OLD DEPRECATED SYSTEM

        // If no policy covered this permission query, we will only grant
        // the permission if the actor's groups have it. Otherwise, we will
        // not allow the user to perform this action.
        if ($actor->isAdmin() || ($actor->hasPermission($ability))) {
            return true;
        }

        return false;
    }
}
