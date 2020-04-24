<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Event\ConfigureModelDates;
use Flarum\Event\ConfigureModelDefaultAttributes;
use Flarum\Event\GetModelRelationship;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
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
    protected $afterSaveCallbacks = [];

    /**
     * An array of callbacks to be run once after the model is deleted.
     *
     * @var callable[]
     */
    protected $afterDeleteCallbacks = [];

    public static $customRelations = [];

    public static $dateAttributes = [];

    public static $defaults = [];

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = [];

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $this->attributes = array_merge($this->attributes, Arr::get(static::$defaults, $class, []));
        }

        // Deprecated in beta 13, remove in beta 14.
        static::$dispatcher->dispatch(
            new ConfigureModelDefaultAttributes($this, $this->attributes)
        );

        $this->attributes = array_map(function ($item) {
            return is_callable($item) ? $item() : $item;
        }, $this->attributes);

        parent::__construct($attributes);
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        static::$dispatcher->dispatch(
            new ConfigureModelDates($this, $this->dates)
        );

        $dates = $this->dates;

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $dates = array_merge($dates, Arr::get(static::$dateAttributes, $class, []));
        }

        return $dates;
    }

    /**
     * Get an attribute from the model. If nothing is found, attempt to load
     * a custom relation method with this key.
     *
     * @param string $key
     * @return mixed
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
    }

    /**
     * Get a custom relation object.
     *
     * @param string $name
     * @return mixed
     */
    protected function getCustomRelation($name)
    {
        foreach (array_merge([static::class], class_parents($this)) as $class) {
            $relation = Arr::get(static::$customRelations, $class.".$name", null);
            if (! is_null($relation)) {
                return $relation($this);
            }
        }

        // Deprecated, remove in beta 14
        return static::$dispatcher->until(
            new GetModelRelationship($this, $name)
        );
    }

    /**
     * Register a callback to be run once after the model is saved.
     *
     * @param callable $callback
     * @return void
     */
    public function afterSave($callback)
    {
        $this->afterSaveCallbacks[] = $callback;
    }

    /**
     * Register a callback to be run once after the model is deleted.
     *
     * @param callable $callback
     * @return void
     */
    public function afterDelete($callback)
    {
        $this->afterDeleteCallbacks[] = $callback;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterSaveCallbacks()
    {
        $callbacks = $this->afterSaveCallbacks;

        $this->afterSaveCallbacks = [];

        return $callbacks;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterDeleteCallbacks()
    {
        $callbacks = $this->afterDeleteCallbacks;

        $this->afterDeleteCallbacks = [];

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $arguments)
    {
        if ($relation = $this->getCustomRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $arguments);
    }
}
