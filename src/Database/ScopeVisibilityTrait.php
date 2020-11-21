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
    protected static $DEFAULT = '*';

    public static function registerVisibilityScoper($scoper, $ability = null)
    {
        $model = static::class;

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
    public function scopeWhereVisibleTo(Builder $query, User $actor, string $ability = 'view')
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
                $fallbackAbility = static::$DEFAULT;
                foreach (Arr::get(static::$visibilityScopers, "$class.$fallbackAbility", []) as $listener) {
                    $listener($actor, $query, $ability);
                }
            }
        }

        return $query;
    }
}
