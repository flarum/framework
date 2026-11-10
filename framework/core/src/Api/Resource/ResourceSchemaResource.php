<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Describe\ResourceDescriber;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Container\Container;
use stdClass;
use Tobyz\JsonApiServer\Context as BaseContext;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;

/**
 * Exposes `ResourceDescriber` descriptors as `GET /api/resource-schemas/{type}`.
 *
 * Only resources opted in through `Extend\ApiResource::describable()` are
 * found, and only for a registered actor (guests get 401 for any id). The
 * actor must pass `ResourceDescriber::canDescribe()`: list or create the
 * described resource. No permission of its own is introduced.
 *
 * The model is a per-request `stdClass` holding the target resource and the
 * lazily built descriptor, so the descriptor is never built for an actor who
 * fails authorization, and nothing is memoised on this (singleton) resource.
 *
 * @extends AbstractResource<stdClass>
 */
class ResourceSchemaResource extends AbstractResource implements Contracts\Findable
{
    /**
     * `ResourceDescriber` depends on `JsonApi`, which builds this resource, so
     * it is resolved lazily from the container to avoid a circular resolution.
     */
    public function __construct(
        protected Container $container
    ) {
    }

    protected function describer(): ResourceDescriber
    {
        return $this->container->make(ResourceDescriber::class);
    }

    public function type(): string
    {
        return 'resource-schemas';
    }

    /**
     * @param stdClass $model
     */
    public function getId(object $model, BaseContext $context): string
    {
        return $model->type;
    }

    public function find(string $id, BaseContext $context): ?object
    {
        // `find()` runs before the endpoint's `authenticated()` check: without
        // this, a guest could probe which types exist and are describable.
        RequestUtil::getActor($context->request)->assertRegistered();

        try {
            $resource = $context->api->getResource($id);
        } catch (ResourceNotFoundException) {
            return null;
        }

        // `JsonApi::getResource()` also accepts a class name; only types count.
        if ($resource->type() !== $id || ! $this->describer()->isDescribable($resource)) {
            return null;
        }

        $schema = new stdClass();
        $schema->type = $id;
        $schema->resource = $resource;
        $schema->descriptor = null;

        return $schema;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->authenticated()
                ->before(function (Context $context) {
                    if (! $this->describer()->canDescribe($context->model->resource, $context->getActor(), $context->request)) {
                        throw new PermissionDeniedException();
                    }
                }),
        ];
    }

    public function fields(): array
    {
        return array_map(
            fn (string $key) => Schema\Attribute::make($key)
                ->visible(fn (stdClass $schema, Context $context) => array_key_exists($key, $this->descriptor($schema, $context)))
                ->get(fn (stdClass $schema, Context $context) => $this->descriptor($schema, $context)[$key]),
            ResourceDescriber::KEYS
        );
    }

    protected function descriptor(stdClass $schema, Context $context): array
    {
        return $schema->descriptor ??= $this->describer()->describe($schema->resource, $context->getActor(), $context->request);
    }
}
