<?php namespace Flarum\Core;

use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Flarum\Core\Support\Exceptions\ValidationFailureException;

class Entity extends Eloquent
{
    protected static $rules = [];

    protected static $messages = [];

    public $timestamps = false;

    /**
     * Validator instance
     * 
     * @var Illuminate\Validation\Validators
     */
    protected $validator;

    public function __construct(array $attributes = [], Validator $validator = null)
    {
        parent::__construct($attributes);

        $this->validator = $validator ?: \App::make('validator');
    }

    public function valid()
    {
        return $this->getValidator()->passes();
    }

    public function assertValid()
    {
        $validation = $this->getValidator();

        if ($validation->fails()) {
            $this->throwValidationException($validation->errors(), $validation->getData());
        }
    }

    protected function getValidator()
    {
        $rules = $this->expandUniqueRules(static::$rules);

        return $this->validator->make($this->attributes, $rules, static::$messages);
    }

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

    protected function throwValidationException($errors, $input)
    {
        $exception = new ValidationFailureException;
        $exception->setErrors($errors)->setInput($input);
        throw $exception;
    }
}
