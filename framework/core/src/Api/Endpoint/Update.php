<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomRoute;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Update as BaseUpdate;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Resource\Updatable;
use function Tobyz\JsonApiServer\json_api_response;

class Update extends BaseUpdate implements Endpoint
{
    use HasAuthorization;
    use HasEagerLoading;
    use HasCustomRoute;

    public function handle(Context $context): ?ResponseInterface
    {
        $segments = explode('/', $context->path());

        if (count($segments) !== 2) {
            return null;
        }

        $context = $context->withModelId($segments[1]);

        $model = $this->execute($context);

        return json_api_response($this->showResource($context, $model));
    }

    public function execute(Context $context): object
    {
        $model = $this->findResource($context, $context->getModelId());

        $context = $context->withResource(
            $resource = $context->resource($context->collection->resource($model, $context)),
        );

        if (!$resource instanceof Updatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($resource), Updatable::class),
            );
        }

        if (!$this->isVisible($context = $context->withModel($model))) {
            throw new ForbiddenException();
        }

        $this->callBeforeHook($context);

        $data = $this->parseData($context);

        $this->assertFieldsValid($context, $data);
        $this->deserializeValues($context, $data);
        $this->assertDataValid($context, $data);
        $this->setValues($context, $data);

        $context = $context->withModel($model = $resource->update($model, $context));

        $this->saveFields($context, $data);

        $model = $this->callAfterHook($context, $model);

        $this->loadRelations(Collection::make([$model]), $context->request);

        return $model;
    }

    public function route(): EndpointRoute
    {
        return new EndpointRoute(
            name: 'update',
            path: $this->path ?? '/{id}',
            method: 'PATCH',
        );
    }
}
