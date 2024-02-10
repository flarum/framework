<?php

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Api\Context;
use Flarum\Api\Schema\Attribute;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

trait ValidatesData
{
    /**
     * @throws ValidationException
     */
    protected function assertDataIsValid(Context $context, array $data, bool $validateAll): void
    {
        $rules = [
            'attributes' => [],
            'relationships' => [],
        ];
        $messages = [];
        $attributes = [];

        foreach ($context->fields($context->resource) as $field) {
            $writable = $field->isWritable($context->withField($field));

            if (! $writable) {
                continue;
            }

            $type = $field instanceof Attribute ? 'attributes' : 'relationships';

            $rules[$type] = array_merge($rules[$type], $field->getValidationRules($context));
            $messages = array_merge($messages, $field->getValidationMessages($context));
            $attributes = array_merge($attributes, $field->getValidationAttributes($context));
        }

        // @todo: merge into a single validator.
        $attributeValidator = resolve(Factory::class)->make($data['attributes'], $rules['attributes'], $messages, $attributes);
        $relationshipValidator = resolve(Factory::class)->make($data['relationships'], $rules['relationships'], $messages, $attributes);

        $attributeValidator->validate();
        $relationshipValidator->validate();
    }
}
