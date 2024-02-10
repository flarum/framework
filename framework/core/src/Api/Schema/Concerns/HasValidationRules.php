<?php

namespace Flarum\Api\Schema\Concerns;

use Flarum\Api\Endpoint\Create;
use Flarum\Api\Endpoint\Update;
use Illuminate\Validation\Rule;
use Tobyz\JsonApiServer\Context;

trait HasValidationRules
{
    /**
     * @var array<array{rule: string|callable, condition: bool|callable}>
     */
    protected array $rules = [];

    /**
     * @var string[]
     */
    protected array $validationMessages = [];

    /**
     * @var string[]
     */
    protected array $validationAttributes = [];

    public function rules(array|string $rules, bool|callable $condition, bool $override = true): static
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $rules = array_map(function ($rule) use ($condition) {
            return compact('rule', 'condition');
        }, $rules);

        $this->rules = $override ? $rules : array_merge($this->rules, $rules);

        return $this;
    }

    public function validationMessages(array $messages): static
    {
        $this->validationMessages = array_merge($this->validationMessages, $messages);

        return $this;
    }

    public function validationAttributes(array $attributes): static
    {
        $this->validationAttributes = array_merge($this->validationAttributes, $attributes);

        return $this;
    }

    public function rule(string|callable $rule, bool|callable $condition = true): static
    {
        $this->rules[] = compact('rule', 'condition');

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getValidationRules(Context $context): array
    {
        $rules = array_map(
            fn ($rule) => $this->evaluate($context, $rule['rule']),
            array_filter(
                $this->rules,
                fn ($rule) => $this->evaluate($context, $rule['condition'])
            )
        );

        return [
            $this->name => $rules
        ];
    }

    public function getValidationMessages(Context $context): array
    {
        return $this->validationMessages;
    }

    public function getValidationAttributes(Context $context): array
    {
        return $this->validationAttributes;
    }

    public function requiredOnCreate(): static
    {
        return $this->rule('required', fn ($model, Context $context) => $context->endpoint instanceof Create);
    }

    public function requiredOnUpdate(): static
    {
        return $this->rule('required', fn ($model, Context $context) => !$context->endpoint instanceof Update);
    }

    public function requiredWith(array $fields, bool|callable $condition): static
    {
        return $this->rule('required_with:' . implode(',', $fields), $condition);
    }

    public function requiredWithout(array $fields, bool|callable $condition): static
    {
        return $this->rule('required_without:' . implode(',', $fields), $condition);
    }

    public function requiredOnCreateWith(array $fields): static
    {
        return $this->requiredWith($fields, fn ($model, Context $context) => $context->endpoint instanceof Create);
    }

    public function requiredOnUpdateWith(array $fields): static
    {
        return $this->requiredWith($fields, fn ($model, Context $context) => $context->endpoint instanceof Update);
    }

    public function requiredOnCreateWithout(array $fields): static
    {
        return $this->requiredWithout($fields, fn ($model, Context $context) => $context->endpoint instanceof Create);
    }

    public function requiredOnUpdateWithout(array $fields): static
    {
        return $this->requiredWithout($fields, fn ($model, Context $context) => $context->endpoint instanceof Update);
    }

    public function nullable(bool $nullable = true): static
    {
        parent::nullable($nullable);

        return $this->rule('nullable');
    }

    public function unique(string $table, string $column, bool $ignorable = false, bool|callable $condition = true): static
    {
        return $this->rule(function ($model, Context $context) use ($table, $column, $ignorable) {
            $rule = Rule::unique($table, $column);

            if ($ignorable && ($modelId = $context->model?->getKey())) {
                $rule = $rule->ignore($modelId, $context->model->getKeyName());
            }

            return $rule;
        }, $condition);
    }
}
