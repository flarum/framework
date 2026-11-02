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
use Tobyz\JsonApiServer\Resource\Updatable;

class Update extends Endpoint
{
    use SavesAndValidatesData;
    use ShowsResources;
    use IncludesData;
    use HasAuthorization;
    use HasCustomHooks;
    use HandlesPrecognition;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'update');
    }

    public function setUp(): void
    {
        $this->route('PATCH', '/{id}')
            ->action(function (Context $context): object {
                $model = $context->model;

                /** @var AbstractResource $resource */
                $resource = $context->resource($context->collection->resource($model, $context));

                $context = $context->withResource($resource);

                if (! $resource instanceof Updatable) {
                    throw new RuntimeException(
                        sprintf('%s must implement %s', get_class($resource), Updatable::class),
                    );
                }

                $this->callBeforeHook($context);

                $data = $this->parseData($context);

                $this->assertFieldsValid($context, $data);
                $this->deserializeValues($context, $data);
                $this->assertDataValid($context, $data);
                $this->setValues($context, $data);

                $context = $context->withModel($model = $resource->updateAction($model, $context));

                $this->saveFields($context, $data);

                return $this->callAfterHook($context, $model);
            })
            ->beforeSerialization(function (Context $context, object $model) {
                $this->loadRelations(Collection::make([$model]), $context, $this->getInclude($context));
            });
    }

    /**
     * Precognitive update: the action's steps up to validation, minus the
     * before hook. Nothing is set on the model, nothing is saved, and no
     * authorization inside before hooks, resource updating/saving hooks or
     * field setters runs: passing does not mean the update would succeed.
     */
    protected function validatePrecognitive(Context $context): void
    {
        /** @var AbstractResource $resource */
        $resource = $context->resource($context->collection->resource($context->model, $context));

        $context = $context->withResource($resource);

        if (! $resource instanceof Updatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($resource), Updatable::class),
            );
        }

        $data = $this->parseData($context);

        $this->assertFieldsValid($context, $data);
        $this->deserializeValues($context, $data);
        $this->assertDataValid($context, $data, $this->precognitiveFields($context));
    }
}
