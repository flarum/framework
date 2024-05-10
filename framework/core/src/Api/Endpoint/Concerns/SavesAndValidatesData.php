<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Api\Schema\Concerns\HasValidationRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Concerns\SavesData;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\ConflictException;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Exception\UnprocessableEntityException;
use Tobyz\JsonApiServer\Schema\Field\Attribute;

trait SavesAndValidatesData
{
    use SavesData {
        parseData as protected parentParseData;
    }

    /**
     * Assert that the field values within a data object pass validation.
     *
     * @param \Flarum\Api\Context $context
     * @throws UnprocessableEntityException
     */
    protected function assertDataValid(Context $context, array $data): void
    {
        $this->mutateDataBeforeValidation($context, $data);

        $collection = $context->collection;

        $rules = [
            'attributes' => [],
            'relationships' => [],
        ];

        $messages = [];
        $attributes = [];

        foreach ($context->fields($context->resource) as $field) {
            $writable = $field->isWritable($context->withField($field));

            if (! $writable || ! in_array(HasValidationRules::class, class_uses_recursive($field))) {
                continue;
            }

            $type = $field instanceof Attribute ? 'attributes' : 'relationships';

            // @phpstan-ignore-next-line
            $rules[$type] = array_merge($rules[$type], $field->getValidationRules($context));
            // @phpstan-ignore-next-line
            $messages = array_merge($messages, $field->getValidationMessages($context));
            // @phpstan-ignore-next-line
            $attributes = array_merge($attributes, $field->getValidationAttributes($context));
        }

        if (method_exists($collection, 'validationFactory')) {
            $factory = $collection->validationFactory();
        } else {
            $loader = new ArrayLoader();
            $translator = new Translator($loader, 'en');
            $factory = new Factory($translator);
        }

        $attributeValidator = $factory->make($data['attributes'], $rules['attributes'], $messages, $attributes);
        $relationshipValidator = $factory->make($data['relationships'], $rules['relationships'], $messages, $attributes);

        $this->validate('attributes', $attributeValidator);
        $this->validate('relationships', $relationshipValidator);
    }

    /**
     * @throws UnprocessableEntityException if any fields do not pass validation.
     */
    protected function validate(string $type, Validator $validator): void
    {
        if ($validator->fails()) {
            $errors = [];

            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[] = [
                    'source' => ['pointer' => "/data/$type/$field"],
                    'detail' => implode(' ', $messages),
                ];
            }

            throw new UnprocessableEntityException($errors);
        }
    }

    protected function mutateDataBeforeValidation(Context $context, array $data): array
    {
        if (method_exists($context->resource, 'mutateDataBeforeValidation')) {
            return $context->resource->mutateDataBeforeValidation($context, $data);
        }

        return $data;
    }

    /**
     * Parse and validate a JSON:API document's `data` member.
     *
     * @throws BadRequestException if the `data` member is invalid.
     */
    final protected function parseData(Context $context): array
    {
        $body = (array) $context->body();

        if (! isset($body['data']) || ! is_array($body['data'])) {
            throw (new BadRequestException('data must be an object'))->setSource([
                'pointer' => '/data',
            ]);
        }

        if (! isset($body['data']['type'])) {
            if (isset($context->collection->resources()[0])) {
                $body['data']['type'] = $context->collection->resources()[0];
            } else {
                throw (new BadRequestException('data.type must be present'))->setSource([
                    'pointer' => '/data/type',
                ]);
            }
        }

        if (isset($context->model)) {
            // commented out to reduce strictness.
//            if (!isset($body['data']['id'])) {
//                throw (new BadRequestException('data.id must be present'))->setSource([
//                    'pointer' => '/data/id',
//                ]);
//            }

            if (isset($body['data']['id']) && $body['data']['id'] !== $context->resource->getId($context->model, $context)) {
                throw (new ConflictException('data.id does not match the resource ID'))->setSource([
                    'pointer' => '/data/id',
                ]);
            }
        } elseif (isset($body['data']['id'])) {
            throw (new ForbiddenException('Client-generated IDs are not supported'))->setSource([
                'pointer' => '/data/id',
            ]);
        }

        if (! in_array($body['data']['type'], $context->collection->resources())) {
            throw (new ConflictException(
                'collection does not support this resource type',
            ))->setSource(['pointer' => '/data/type']);
        }

        if (array_key_exists('attributes', $body['data']) && ! is_array($body['data']['attributes'])) {
            throw (new BadRequestException('data.attributes must be an object'))->setSource([
                'pointer' => '/data/attributes',
            ]);
        }

        if (array_key_exists('relationships', $body['data']) && ! is_array($body['data']['relationships'])) {
            throw (new BadRequestException('data.relationships must be an object'))->setSource([
                'pointer' => '/data/relationships',
            ]);
        }

        return array_merge(['attributes' => [], 'relationships' => []], $body['data']);
    }
}
