<?php namespace Flarum\Core\Models;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\EventGenerator;
use LogicException;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
 *
 * @todo Refactor out validation, either into a trait or into a dependency.
 *       The following requirements need to be fulfilled:
 *       - Ability for extensions to alter ruleset (add, modify, remove).
 *       - Ability for extensions to add custom rules to the validator instance.
 *       - Use Flarum's translator with the validator instance.
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
     * The validation rules for this model.
     *
     * @var array
     */
    public static $rules = [];

    /**
     * An array of custom relation methods, grouped by subclass.
     *
     * @var array
     */
    protected static $relationMethods = [];

    /**
     * The validation factory instance.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected static $validator;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Before the model is saved, validate it. If validation fails, an
        // exception will be thrown, preventing the model from saving.
        static::saving(function ($model) {
            $model->assertValid();
        });
    }

    /**
     * Set the validation factory instance.
     *
     * @param \Illuminate\Contracts\Validation\Factory $validator
     */
    public static function setValidator(Factory $validator)
    {
        static::$validator = $validator;
    }

    /**
     * Check whether the model is valid in its current state.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->makeValidator()->passes();
    }

    /**
     * Throw an exception if the model is not valid in its current state.
     *
     * @return void
     *
     * @throws \Flarum\Core\ValidationFailureException
     */
    public function assertValid()
    {
        $validator = $this->makeValidator();
        if ($validator->fails()) {
            $this->throwValidationFailureException($validator);
        }
    }

    protected function throwValidationFailureException($validator)
    {
        throw (new ValidationFailureException)
            ->setErrors($validator->errors())
            ->setInput($validator->getData());
    }

    /**
     * Make a new validator instance for this model.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function makeValidator()
    {
        $dirty = $this->getDirty();

        $rules = $this->expandUniqueRules(array_only(static::$rules, array_keys($dirty)));

        // TODO: translation
        $messages = [
            'unique'    => 'That :attribute has already been taken.',
            'email'     => 'The :attribute must be a valid email address.',
            'alpha_num' => 'The :attribute may only contain letters and numbers.'
        ];

        return static::$validator->make($dirty, $rules, $messages);
    }

    /**
     * Expand 'unique' rules in a set of validation rules into a fuller form
     * that Laravel's validator can understand.
     *
     * @param  array  $rules
     * @return array
     */
    protected function expandUniqueRules($rules)
    {
        foreach ($rules as $column => &$ruleset) {
            if (is_string($ruleset)) {
                $ruleset = explode('|', $ruleset);
            }
            foreach ($ruleset as &$rule) {
                if (strpos($rule, 'unique') === 0) {
                    $parts = explode(':', $rule);
                    $key = $this->getKey() ?: 'NULL';
                    $rule = 'unique:'.$this->getTable().','.$column.','.$key.','.$this->getKeyName();
                    if (! empty($parts[1])) {
                        $wheres = explode(',', $parts[1]);
                        foreach ($wheres as &$where) {
                            $where .= ','.$this->$where;
                        }
                        $rule .= ','.implode(',', $wheres);
                    }
                }
            }
        }

        return $rules;
    }

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
    public function getCustomRelationship($name)
    {
        $relation = static::$relationMethods[get_called_class()][$name]($this);

        if (! $relation instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                . 'Illuminate\Database\Eloquent\Relations\Relation');
        }

        return $this->relations[$method] = $relation->getResults();
    }

    /**
     * Add a custom relation to the model.
     *
     * @param string $name The name of the relation.
     * @param callable $callback The callback to execute. This should return an
     *     object of type Illuminate\Database\Eloquent\Relations\Relation.
     * @return void
     */
    public static function setRelationMethod($name, callable $callback)
    {
        static::$relationMethods[get_called_class()][$name] = $callback;
    }
}
