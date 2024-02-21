<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\HasCustomRoute;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Create as BaseCreate;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Resource\Creatable;
use function Tobyz\JsonApiServer\json_api_response;

class Create extends BaseCreate implements Endpoint
{
    use HasAuthorization;
    use HasEagerLoading;
    use HasCustomRoute;
    use HasCustomHooks;

    public function handle(Context $context): ?ResponseInterface
    {
        $model = $this->execute($context);

        return json_api_response($document = $this->showResource($context, $model))
            ->withStatus(201)
            ->withHeader('Location', $document['data']['links']['self']);
    }

    public function execute(Context $context): object
    {
        $collection = $context->collection;

        if (!$collection instanceof Creatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($collection), Creatable::class),
            );
        }

        if (!$this->isVisible($context)) {
            throw new ForbiddenException();
        }

        $this->callBeforeHook($context);

        $data = $this->parseData($context);

        $context = $context
            ->withResource($resource = $context->resource($data['type']))
            ->withModel($model = $collection->newModel($context));

        $this->assertFieldsValid($context, $data);
        $this->fillDefaultValues($context, $data);
        $this->deserializeValues($context, $data);
        $this->assertDataValid($context, $data);

        $this->setValues($context, $data);

        $context = $context->withModel($model = $resource->create($model, $context));

        $this->saveFields($context, $data);

        $model = $this->callAfterHook($context, $model);

        $this->loadRelations(Collection::make([$model]), $context->request, $this->getInclude($context));

        return $model;
    }

    public function route(): EndpointRoute
    {
        return new EndpointRoute(
            name: 'create',
            path: $this->path ?? '/',
            method: 'POST',
        );
    }
}
