<?php namespace Flarum\Core;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Flarum\Core\Exceptions\ValidationFailureException;
use LogicException;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected static $dateAttributes = [];

    /**
     * An array of custom relation methods, grouped by subclass.
     *
     * @var array
     */
    protected static $relationMethods = [];

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        return array_merge(static::$dateAttributes, $this->dates);
    }

    /**
     * Add an attribute to be converted to a date.
     *
     * @param string $attribute
     */
    public static function addDateAttribute($attribute)
    {
        static::$dateAttributes[] = $attribute;
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
        if (isset(static::$relationMethods[get_called_class()][$key])) {
            return $this->getCustomRelationship($key);
        }
    }

    /**
     * Get a relationship value from a custom relationship method.
     *
     * @param string $name
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getCustomRelationship($name)
    {
        $relation = static::$relationMethods[get_called_class()][$name]($this);

        if (! $relation instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                . 'Illuminate\Database\Eloquent\Relations\Relation');
        }

        return $this->relations[$name] = $relation->getResults();
    }

    /**
     * Add a custom relation to the model.
     *
     * @param string $name The name of the relation.
     * @param callable $callback The callback to execute. This should return an
     *     object of type Illuminate\Database\Eloquent\Relations\Relation.
     */
    public static function setRelationMethod($name, callable $callback)
    {
        static::$relationMethods[get_called_class()][$name] = $callback;
    }
}
