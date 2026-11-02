<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\HandlesPrecognition;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\IncludesData;
use Flarum\Api\Endpoint\Concerns\SavesAndValidatesData;
use Flarum\Api\Endpoint\Concerns\ShowsResources;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Database\Eloquent\Collection;
use RuntimeException;
use Tobyz\JsonApiServer\Resource\Creatable;

use function Tobyz\JsonApiServer\has_value;
use function Tobyz\JsonApiServer\json_api_response;
use function Tobyz\JsonApiServer\set_value;

class Create extends Endpoint
{
    use SavesAndValidatesData;
    use ShowsResources;
    use IncludesData;
    use HasAuthorization;
    use HasCustomHooks;
    use HandlesPrecognition;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'create');
    }

    public function setUp(): void
    {
        $this->route('POST', '/')
            ->action(function (Context $context): ?object {
                if (str_contains($context->path(), '/')) {
                    return null;
                }

                $collection = $context->collection;

                if (! $collection instanceof Creatable) {
                    throw new RuntimeException(
                        sprintf('%s must implement %s', get_class($collection), Creatable::class),
                    );
                }

                $this->callBeforeHook($context);

                $data = $this->parseData($context);

                /** @var AbstractResource $resource */
                $resource = $context->resource($data['type']);

                $context = $context
                    ->withResource($resource)
                    ->withModel($model = $collection->newModel($context));

                $this->assertFieldsValid($context, $data);
                $this->fillDefaultValues($context, $data);
                $this->deserializeValues($context, $data);
                $data = $this->mutateDataBeforeValidation($context, $data);
                $this->assertDataValid($context, $data);
                $this->setValues($context, $data);

                $context = $context->withModel($model = $resource->createAction($model, $context));

                $this->saveFields($context, $data);

                return $this->callAfterHook($context, $model);
            })
            ->beforeSerialization(function (Context $context, object $model) {
                $this->loadRelations(Collection::make([$model]), $context, $this->getInclude($context));
            })
            ->response(function (Context $context, object $model) {
                return json_api_response($document = $this->showResource($context, $model))
                    ->withStatus(201)
                    ->withHeader('Location', $document['data']['links']['self']);
            });
    }

    /**
     * Precognitive create: the action's steps up to validation, minus the
     * before hook. Nothing is set on the model, nothing is saved, and no
     * authorization inside before hooks, resource creating/saving hooks or
     * field setters runs: passing does not mean the create would succeed.
     */
    protected function validatePrecognitive(Context $context): void
    {
        $collection = $context->collection;

        if (! $collection instanceof Creatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($collection), Creatable::class),
            );
        }

        $data = $this->parseData($context);

        /** @var AbstractResource $resource */
        $resource = $context->resource($data['type']);

        $context = $context
            ->withResource($resource)
            ->withModel($collection->newModel($context));

        $this->assertFieldsValid($context, $data);
        $this->fillDefaultValues($context, $data);
        $this->deserializeValues($context, $data);
        $data = $this->mutateDataBeforeValidation($context, $data);
        $this->assertDataValid($context, $data, $this->precognitiveFields($context));
    }

    final protected function fillDefaultValues(Context $context, array &$data): void
    {
        foreach ($context->fields($context->resource) as $field) {
            if (($default = $field->default) && ! has_value($data, $field)) {
                set_value($data, $field, $default($context->withField($field)));
            }
        }
    }
}
