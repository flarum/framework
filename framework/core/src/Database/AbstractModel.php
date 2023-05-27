<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LogicException;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
 */
abstract class AbstractModel extends Eloquent
{
    /**
     * Indicates if the model should be timestamped. Turn off by default.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * An array of callbacks to be run once after the model is saved.
     *
     * @var callable[]
     */
    protected array $afterSaveCallbacks = [];

    /**
     * An array of callbacks to be run once after the model is deleted.
     *
     * @var callable[]
     */
    protected array $afterDeleteCallbacks = [];

    /**
     * @internal
     */
    public static array $customRelations = [];

    /**
     * @internal
     */
    public static array $customCasts = [];

    /**
     * @internal
     */
    public static array $defaults = [];

    /**
     * An alias for the table name, used in queries.
     *
     * @internal
     */
    protected ?string $tableAlias = null;

    public static function boot()
    {
        parent::boot();

        static::saved(function (self $model) {
            foreach ($model->releaseAfterSaveCallbacks() as $callback) {
                $callback($model);
            }
        });

        static::deleted(function (self $model) {
            foreach ($model->releaseAfterDeleteCallbacks() as $callback) {
                $callback($model);
            }
        });
    }

    public function __construct(array $attributes = [])
    {
        $this->attributes = [];

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $this->attributes = array_merge($this->attributes, Arr::get(static::$defaults, $class, []));
        }

        $this->attributes = array_map(function ($item) {
            return is_callable($item) ? $item($this) : $item;
        }, $this->attributes);

        parent::__construct($attributes);
    }

    public function getCasts(): array
    {
        $casts = parent::getCasts();

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $casts = array_merge($casts, Arr::get(static::$customCasts, $class, []));
        }

        return $casts;
    }

    /**
     * Get an attribute from the model. If nothing is found, attempt to load
     * a custom relation method with this key.
     */
    public function getAttribute($key)
    {
        if (! is_null($value = parent::getAttribute($key))) {
            return $value;
        }

        // If a custom relation with this key has been set up, then we will load
        // and return results from the query and hydrate the relationship's
        // value on the "relationships" array.
        if (! $this->relationLoaded($key) && ($relation = $this->getCustomRelation($key))) {
            if (! $relation instanceof Relation) {
                throw new LogicException(
                    'Relationship method must return an object of type '.Relation::class
                );
            }

            return $this->relations[$key] = $relation->getResults();
        }

        return null;
    }

    /**
     * Get a custom relation object.
     */
    protected function getCustomRelation(string $name): mixed
    {
        foreach (array_merge([static::class], class_parents($this)) as $class) {
            $relation = Arr::get(static::$customRelations, $class.".$name");
            if (! is_null($relation)) {
                return $relation($this);
            }
        }

        return null;
    }

    /**
     * Register a callback to be run once after the model is saved.
     */
    public function afterSave(callable $callback): void
    {
        $this->afterSaveCallbacks[] = $callback;
    }

    /**
     * Register a callback to be run once after the model is deleted.
     */
    public function afterDelete(callable $callback): void
    {
        $this->afterDeleteCallbacks[] = $callback;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterSaveCallbacks(): array
    {
        $callbacks = $this->afterSaveCallbacks;

        $this->afterSaveCallbacks = [];

        return $callbacks;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterDeleteCallbacks(): array
    {
        $callbacks = $this->afterDeleteCallbacks;

        $this->afterDeleteCallbacks = [];

        return $callbacks;
    }

    public function __call($method, $parameters)
    {
        if ($relation = $this->getCustomRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $parameters);
    }

    public function newModelQuery()
    {
        $query = parent::newModelQuery();

        if ($this->tableAlias) {
            $query->from($this->getTable().' as '.$this->tableAlias);
        }

        return $query;
    }

    public function qualifyColumn($column)
    {
        if (Str::contains($column, '.')) {
            return $column;
        }

        return ($this->tableAlias ?? $this->getTable()).'.'.$column;
    }

    public function withTableAlias(callable $callback): mixed
    {
        static $aliasCount = 0;
        $this->tableAlias = 'flarum_reserved_'.++$aliasCount;

        $result = $callback();

        $this->tableAlias = null;

        return $result;
    }

    public function newCollection(array $models = []): Collection
    {
        return new Collection($models);
    }
}
