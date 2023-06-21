<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class Gate
{
    protected const EVALUATION_CRITERIA_PRIORITY = [
        AbstractPolicy::FORCE_DENY => false,
        AbstractPolicy::FORCE_ALLOW => true,
        AbstractPolicy::DENY => false,
        AbstractPolicy::ALLOW => true,
    ];

    /**
     * @var AbstractPolicy[]
     */
    protected array $policies = [];

    public function __construct(
        protected Container $container,
        /** @var class-string<AbstractPolicy>[] $policyClasses */
        protected array $policyClasses
    ) {
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param class-string<AbstractModel>|AbstractModel|null $model
     */
    public function allows(User $actor, string $ability, string|AbstractModel|null $model): bool
    {
        $results = [];
        /** @var AbstractPolicy[] $appliedPolicies */
        $appliedPolicies = [];

        if ($model) {
            $modelClasses = is_string($model) ? [$model] : array_merge(class_parents($model), [get_class($model)]);

            foreach ($modelClasses as $class) {
                $appliedPolicies = array_merge($appliedPolicies, $this->getPolicies($class));
            }
        } else {
            $appliedPolicies = $this->getPolicies(AbstractPolicy::GLOBAL);
        }

        foreach ($appliedPolicies as $policy) {
            $results[] = $policy->checkAbility($actor, $ability, $model);
        }

        foreach (static::EVALUATION_CRITERIA_PRIORITY as $criteria => $decision) {
            if (in_array($criteria, $results, true)) {
                return $decision;
            }
        }

        // If no policy covered this permission query, we will only grant
        // the permission if the actor's groups have it. Otherwise, we will
        // not allow the user to perform this action.
        if ($actor->isAdmin() || $actor->hasPermission($ability)) {
            return true;
        }

        return false;
    }

    /**
     * Get all policies for a given model and ability.
     */
    protected function getPolicies(string $model): array
    {
        $compiledPolicies = Arr::get($this->policies, $model);

        if (is_null($compiledPolicies)) {
            $policyClasses = Arr::get($this->policyClasses, $model, []);
            $compiledPolicies = array_map(function ($policyClass) {
                return $this->container->make($policyClass);
            }, $policyClasses);
            Arr::set($this->policies, $model, $compiledPolicies);
        }

        return $compiledPolicies;
    }
}
