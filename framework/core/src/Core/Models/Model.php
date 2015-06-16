<?php namespace Flarum\Core\Models;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\MappedMorphToTrait;

class Model extends Eloquent
{
    use EventGenerator;
    use MappedMorphToTrait;

    /**
     * Disable timestamps.
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    protected static $rules = [];

    /**
     * The custom relations on this model, registered by extensions.
     *
     * @var array
     */
    protected static $relationships = [];

    /**
     * The forum model instance.
     *
     * @var \Flarum\Core\Models\Forum
     */
    protected static $forum;

    /**
     * The validation factory instance.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected static $validator;

    /**
     * Validate the model on save.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->assertValid();
        });
    }

    /**
     * Define the relationship with the forum.
     *
     * @return \Flarum\Core\Models\Forum
     */
    public function forum()
    {
        return static::$forum;
    }

    /**
     * Set the forum model instance.
     *
     * @param \Flarum\Core\Models\Forum $forum
     */
    public static function setForum(Forum $forum)
    {
        static::$forum = $forum;
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

        return static::$validator->make($dirty, $rules);
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

    public function isRelationLoaded($relation)
    {
        return array_key_exists($relation, $this->relations);
    }

    public function getRelation($relation)
    {
        if (isset($this->$relation)) {
            return $this->$relation;
        }

        if (! $this->isRelationLoaded($relation)) {
            $this->relations[$relation] = $this->$relation()->getResults();
        }

        return $this->relations[$relation];
    }

    /**
     * Add a custom relationship to the model.
     *
     * @param string $name The name of the relationship.
     * @param Closure $callback The callback to execute. This should return an
     *     Eloquent relationship object.
     * @return void
     */
    public static function addRelationship($name, $callback)
    {
        static::$relationships[$name] = $callback;
    }

    /**
     * Check for and execute custom relationships.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$relationships[$name])) {
            array_unshift($arguments, $this);
            return call_user_func_array(static::$relationships[$name], $arguments);
        }

        return parent::__call($name, $arguments);
    }
}
