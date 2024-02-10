<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomRoute;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Flarum\Api\Endpoint\Concerns\HasHooks;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ResponseInterface;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Concerns\FindsResources;
use Tobyz\JsonApiServer\Endpoint\Concerns\ShowsResources;
use Tobyz\JsonApiServer\Endpoint\Show as BaseShow;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use function Tobyz\JsonApiServer\json_api_response;

class Show extends BaseShow implements Endpoint
{
    use FindsResources;
    use ShowsResources;
    use HasAuthorization;
    use HasEagerLoading;
    use HasCustomRoute;
    use ExtractsListingParams;
    use HasHooks;

    public function handle(Context $context): ?ResponseInterface
    {
        $segments = explode('/', $context->path());

        $path = $this->route()->path;

        if ($path !== '/' && count($segments) !== 2) {
            return null;
        }

        $context = $context->withModelId($path === '/' ? 1 : $segments[1]);

        $this->callBeforeHook($context);

        $model = $this->execute($context);

        if (!$this->isVisible($context = $context->withModel($model))) {
            throw new ForbiddenException();
        }

        $model = $this->callAfterHook($context, $model);

        $this->loadRelations(Collection::make([$model]), $context->request);

        return json_api_response($this->showResource($context, $model));
    }

    public function execute(Context $context): object
    {
        return $this->findResource($context, $context->getModelId());
    }

    public function route(): EndpointRoute
    {
        return new EndpointRoute(
            name: 'show',
            path: $this->path ?? '/{id}',
            method: 'GET',
        );
    }
}
