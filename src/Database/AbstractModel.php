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
     * An array of custom relationships defined by extensions.
     */
    protected static $customRelations = [];

    protected static $dateCallbacks = [];

    protected static $defaultAttributeCallbacks = [];

    public static function addCustomRelation(string $from, string $name, $relation)
    {
        if (! array_key_exists($from, static::$customRelations)) {
            static::$customRelations[$from] = [];
        }

        static::$customRelations[$from][$name] = $relation;
    }

    public static function addDateCallback(string $model, $callback)
    {
        if (! array_key_exists($model, static::$dateCallbacks)) {
            static::$dateCallbacks[$model] = [];
        }

        static::$dateCallbacks[$model][] = $callback;
    }

    public static function addDefaultAttributeCallback(string $model, $callback)
    {
        if (! array_key_exists($model, static::$defaultAttributeCallbacks)) {
            static::$defaultAttributeCallbacks[$model] = [];
        }

        static::$defaultAttributeCallbacks[$model][] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        $defaults = [];

        // Deprecated in beta 13, remove in beta 14.
        static::$dispatcher->dispatch(
            new ConfigureModelDefaultAttributes($this, $defaults)
        );

        foreach (Arr::get(static::$defaultAttributeCallbacks, static::class, []) as $callback) {
            $defaults = $callback($defaults);
        }

        $this->attributes = $defaults;

        parent::__construct($attributes);
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        static $dates = [];

        $class = get_class($this);

        if (! isset($dates[$class])) {
            static::$dispatcher->dispatch(
                new ConfigureModelDates($this, $this->dates)
            );

            $dates[$class] = $this->dates;
        }

        foreach (Arr::get(static::$dateCallbacks, static::class, []) as $callback) {
            $dates[$class] = $callback($dates[$class]);
        }

        return $dates[$class];
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
        $relation = Arr::get(Arr::get(static::$customRelations, static::class, []), $name, null);

        if (! is_null($relation)) {
            return $relation($this);
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
