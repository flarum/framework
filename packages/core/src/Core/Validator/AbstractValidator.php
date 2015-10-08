<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Validator;

use Flarum\Database\AbstractModel;
use Flarum\Event\ConfigureModelValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

class AbstractValidator
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Factory $validator
     */
    public function __construct(Factory $validator, Dispatcher $events)
    {
        $this->validator = $validator;
        $this->events = $events;
    }

    /**
     * Check whether a model is valid.
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function valid(AbstractModel $model)
    {
        return $this->makeValidator($model)->passes();
    }

    /**
     * Throw an exception if a model is not valid.
     *
     * @throws ValidationException
     */
    public function assertValid(AbstractModel $model)
    {
        $validator = $this->makeValidator($model);

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
     * @param AbstractModel $model
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(AbstractModel $model)
    {
        $rules = $this->expandUniqueRules($this->rules, $model);

        $validator = $this->validator->make($model->getAttributes(), $rules);

        $this->events->fire(
            new ConfigureModelValidator($model, $validator)
        );

        return $validator;
    }

    /**
     * Expand 'unique' rules in a set of validation rules into a fuller form
     * that Laravel's validator can understand.
     *
     * @param array $rules
     * @param AbstractModel $model
     * @return array
     */
    protected function expandUniqueRules($rules, AbstractModel $model)
    {
        foreach ($rules as $attribute => &$ruleset) {
            if (is_string($ruleset)) {
                $ruleset = explode('|', $ruleset);
            }

            foreach ($ruleset as &$rule) {
                if (strpos($rule, 'unique') === 0) {
                    $rule = $this->expandUniqueRule($attribute, $rule, $model);
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
     * @param AbstractModel $model
     * @return string
     */
    protected function expandUniqueRule($attribute, $rule, AbstractModel $model)
    {
        $parts = explode(':', $rule);
        $key = $model->getKey() ?: 'NULL';
        $rule = 'unique:'.$model->getTable().','.$attribute.','.$key.','.$model->getKeyName();

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
