<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Event\ScopeModelVisibility;
use Flarum\Group\Group;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait ScopeVisibilityTrait
{
    protected static $visibilityScopers = [];
    protected static $DEFAULT = 'default';

    public static function registerVisibilityScoper(string $model, $scoper, $ability = null)
    {
        if ($ability == null) {
            $ability = static::$DEFAULT;
        }

        if (!Arr::has(static::$visibilityScopers, "$model.$ability")) {
            Arr::set(static::$visibilityScopers, "$model.$ability", []);
        }

        static::$visibilityScopers[$model][$ability][] = $scoper;
    }


    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     */
    public function scopeWhereVisibleTo(Builder $query, User $actor)
    {
        return $this->scopeWhereVisibleWithAbility($query, $actor, 'view');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     */
    public function scopeWhereVisibleWithAbility(Builder $query, User $actor, string $ability)
    {
        $listeners = static::$dispatcher->getListeners(ScopeModelVisibility::class);
        foreach ($listeners as $listener) {
            $event = new ScopeModelVisibility($query, $actor, $ability);
            $listener(get_class($event), Arr::wrap($event));
        }

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (!empty($newListeners = Arr::get(static::$visibilityScopers, "$class.$ability", []))) {
                foreach ($newListeners as $listener) {
                    $listener($actor, $query);
                }
            } else {
                foreach (Arr::get(static::$visibilityScopers, "$class.*", []) as $listener) {
                    $listener($actor, $query, $ability);
                }
            }
        }

        return $query;
    }
}
