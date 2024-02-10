<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomRoute;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Concerns\FindsResources;
use Tobyz\JsonApiServer\Endpoint\Delete as BaseDelete;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Resource\Deletable;
use function Tobyz\JsonApiServer\json_api_response;

class Delete extends BaseDelete implements Endpoint
{
    use HasAuthorization;
    use FindsResources;
    use HasCustomRoute;

    /** {@inheritdoc} */
    public function handle(Context $context): ?ResponseInterface
    {
        $segments = explode('/', $context->path());

        if (count($segments) !== 2) {
            return null;
        }

        $context = $context->withModelId($segments[1]);

        $this->execute($context);

        if ($meta = $this->serializeMeta($context)) {
            return json_api_response(['meta' => $meta]);
        }

        return new Response(204);
    }

    public function execute(Context $context): bool
    {
        $model = $this->findResource($context, $context->getModelId());

        $context = $context->withResource(
            $resource = $context->resource($context->collection->resource($model, $context)),
        );

        if (!$resource instanceof Deletable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($resource), Deletable::class),
            );
        }

        if (!$this->isVisible($context = $context->withModel($model))) {
            throw new ForbiddenException();
        }

        $resource->delete($model, $context);

        return true;
    }

    public function route(): EndpointRoute
    {
        return new EndpointRoute(
            name: 'delete',
            path: $this->path ?? '/{id}',
            method: 'DELETE',
        );
    }
}
