<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait ScopeVisibilityTrait
{
    /**
     * @todo: define clear scoper interfaces.
     *
     * @var array<string, array<string, callable(User, Builder $query, ?string $ability): void>>
     */
    protected static array $visibilityScopers = [];

    public static function registerVisibilityScoper($scoper, ?string $ability = null): void
    {
        $model = static::class;

        $ability ??= '*';

        if (! Arr::has(static::$visibilityScopers, "$model.$ability")) {
            Arr::set(static::$visibilityScopers, "$model.$ability", []);
        }

        static::$visibilityScopers[$model][$ability][] = $scoper;
    }

    /**
     * Scope a query to only include records that are visible to a user.
     */
    public function scopeWhereVisibleTo(Builder $query, User $actor, string $ability = 'view'): Builder
    {
        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            foreach (Arr::get(static::$visibilityScopers, "$class.*", []) as $listener) {
                $listener($actor, $query, $ability);
            }
            foreach (Arr::get(static::$visibilityScopers, "$class.$ability", []) as $listener) {
                $listener($actor, $query);
            }
        }

        return $query;
    }
}
