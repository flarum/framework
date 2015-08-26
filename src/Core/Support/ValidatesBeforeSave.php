<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

use Flarum\Events\ModelValidator;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesBeforeSave
{
    /**
     * The validation factory instance.
     *
     * @var Factory
     */
    protected static $validator;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function bootValidatesBeforeSave()
    {
        // Before the model is saved, validate it. If validation fails, an
        // exception will be thrown, preventing the model from saving.
        static::saving(function ($model) {
            $model->assertValid();
        });
    }

    /**
     * Set the validation factory instance.
     *
     * @param Factory $validator
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
     * @throws ValidationException
     */
    public function assertValid()
    {
        $validator = $this->makeValidator();

        if ($validator->fails()) {
            $this->throwValidationException($validator);
        }
    }

    /**
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function throwValidationException(Validator $validator)
    {
        throw new ValidationException($validator);
    }

    /**
     * Make a new validator instance for this model.
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator()
    {
        $rules = $this->expandUniqueRules($this->rules);

        $validator = static::$validator->make($this->getAttributes(), $rules);

        event(new ModelValidator($this, $validator));

        return $validator;
    }

    /**
     * Expand 'unique' rules in a set of validation rules into a fuller form
     * that Laravel's validator can understand.
     *
     * @param array $rules
     * @return array
     */
    protected function expandUniqueRules($rules)
    {
        foreach ($rules as $attribute => &$ruleset) {
            if (is_string($ruleset)) {
                $ruleset = explode('|', $ruleset);
            }

            foreach ($ruleset as &$rule) {
                if (strpos($rule, 'unique') === 0) {
                    $rule = $this->expandUniqueRule($attribute, $rule);
                }
            }
        }

        return $rules;
    }

    /**
     * Expand a 'unique' rule into a fuller form that Laravel's validator can
     * understand, based on this model's properties.
     *
     * @param string $attribute
     * @param string $rule
     * @return string
     */
    protected function expandUniqueRule($attribute, $rule)
    {
        $parts = explode(':', $rule);
        $key = $this->getKey() ?: 'NULL';
        $rule = 'unique:'.$this->getTable().','.$attribute.','.$key.','.$this->getKeyName();

        if (! empty($parts[1])) {
            $wheres = explode(',', $parts[1]);

            foreach ($wheres as &$where) {
                $where .= ','.$this->$where;
            }

            $rule .= ','.implode(',', $wheres);
        }

        return $rule;
    }
}
