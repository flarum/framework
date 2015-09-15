<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Events\ModelDates;
use Flarum\Events\ModelRelationship;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
 *
 * Also has a scope method `whereVisibleTo` that scopes a query to only include
 * records that the user has permission to see.
 */
abstract class Model extends Eloquent
{
    /**
     * Indicates if the model should be timestamped. Turn off by default.
     *
     * @var boolean
     */
    public $timestamps = false;

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
            event(new ModelDates($this, $this->dates));

            $dates[$class] = $this->dates;
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
                throw new LogicException('Relationship method must return an object of type '
                    . 'Illuminate\Database\Eloquent\Relations\Relation');
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
        return static::$dispatcher->until(
            new ModelRelationship($this, $name)
        );
    }

    /**
     * @inheritdoc
     */
    public function __call($method, $arguments)
    {
        if ($relation = $this->getCustomRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $arguments);
    }
}
