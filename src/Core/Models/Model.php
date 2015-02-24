<?php namespace Flarum\Core\Models;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\EventGenerator;

class Model extends Eloquent
{
    use EventGenerator;

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
        if ($this->makeValidator()->fails()) {
            throw (new ValidationFailureException)
                ->setErrors($validation->errors())
                ->setInput($validation->getData());
        }
    }

    /**
     * Make a new validator instance for this model.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function makeValidator()
    {
        $rules = $this->expandUniqueRules(static::$rules);

        return $this->validator->make($this->attributes, $rules, static::$messages);
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
     * Assert that the user has permission to view this model, throwing an
     * exception if they don't.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return void
     *
     * @throws  \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function assertVisibleTo(User $user)
    {
        if (! $this->can($user, 'view')) {
            throw new ModelNotFoundException;
        }
    }

    /**
     * Assert that the user has a certain permission for this model, throwing
     * an exception if they don't.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @param  string  $permission
     * @return void
     *
     * @throws  \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function assertCan(User $user, $permission)
    {
        if (! $this->can($user, $permission)) {
            throw new PermissionDeniedException;
        }
    }
}
