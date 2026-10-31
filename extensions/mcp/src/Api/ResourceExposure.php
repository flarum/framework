<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Http\RequestUtil;
use Flarum\Mcp\Capability\CapabilityRegistry;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use Throwable;

/**
 * Which JSON:API types and CRUD operations the generic tools may reach.
 *
 * Reads are exposed for every visible type; writes only where allow-listed.
 * This decides what is *offered*, leaving out what the endpoint's own gates
 * already refuse the actor; every call still runs those gates through
 * `JsonApi::handle()`.
 */
class ResourceExposure
{
    public const array READ_OPERATIONS = ['index', 'show'];
    public const array WRITE_OPERATIONS = ['create', 'update', 'delete'];

    /**
     * @var array<string, class-string<Endpoint\Endpoint>>
     */
    protected const array ENDPOINT_CLASSES = [
        'index' => Endpoint\Index::class,
        'show' => Endpoint\Show::class,
        'create' => Endpoint\Create::class,
        'update' => Endpoint\Update::class,
        'delete' => Endpoint\Delete::class,
    ];

    public function __construct(
        protected JsonApi $api,
        protected CapabilityRegistry $registry,
    ) {
    }

    /**
     * Every type that is not hidden, whatever the actor.
     *
     * @return array<string, AbstractResource>
     */
    public function resources(): array
    {
        $resources = [];

        foreach ($this->api->resources as $type => $resource) {
            if ($resource instanceof AbstractResource && ! $this->registry->isHidden($type)) {
                $resources[$type] = $resource;
            }
        }

        ksort($resources);

        return $resources;
    }

    public function resource(string $type): ?AbstractResource
    {
        return $this->resources()[$type] ?? null;
    }

    /**
     * The operations of a type the actor is offered.
     *
     * @return array<string, Endpoint\Endpoint> keyed by operation
     */
    public function operations(AbstractResource $resource, User $actor): array
    {
        $type = $resource->type();
        $operations = [];

        foreach ($resource->resolveEndpoints() as $endpoint) {
            if (! $endpoint instanceof Endpoint\Endpoint) {
                continue;
            }

            $operation = $endpoint->name;
            $class = static::ENDPOINT_CLASSES[$operation] ?? null;

            if (! $class || ! $endpoint instanceof $class) {
                continue;
            }

            if (in_array($operation, static::WRITE_OPERATIONS, true) && ! $this->registry->writesAllowed($type, $operation)) {
                continue;
            }

            if ($this->registry->isEndpointHidden($type, $operation)) {
                continue;
            }

            if ($this->isAdminOnly($endpoint) && ! $actor->isAdmin()) {
                continue;
            }

            if (! $this->isAuthorized($resource, $endpoint, $actor)) {
                continue;
            }

            $operations[$operation] = $endpoint;
        }

        return $operations;
    }

    /**
     * @return array<string, array<string, Endpoint\Endpoint>> type => operation => endpoint
     */
    public function exposed(User $actor): array
    {
        $exposed = [];

        foreach ($this->resources() as $type => $resource) {
            if ($operations = $this->operations($resource, $actor)) {
                $exposed[$type] = $operations;
            }
        }

        return $exposed;
    }

    /**
     * @return list<string>
     */
    public function typesWith(string $operation, User $actor): array
    {
        $types = [];

        foreach ($this->exposed($actor) as $type => $operations) {
            if (isset($operations[$operation])) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public function endpoint(string $type, string $operation, User $actor): ?Endpoint\Endpoint
    {
        $resource = $this->resource($type);

        return $resource ? ($this->operations($resource, $actor)[$operation] ?? null) : null;
    }

    /**
     * The endpoint's own `authenticated()` / `can()` / `visible()` gates, where
     * they can be decided without a model: in full for index and create, which
     * act on no model; for the others only a plain `authenticated()`. A gate
     * that needs a model to decide (it throws here) is left to the call itself.
     */
    protected function isAuthorized(AbstractResource $resource, Endpoint\Endpoint $endpoint, User $actor): bool
    {
        $context = (new Context($this->api, RequestUtil::withActor(new ServerRequest(), $actor)))
            ->withCollection($resource)
            ->withResource($resource)
            ->withEndpoint($endpoint);

        try {
            if ($endpoint instanceof Endpoint\Index || $endpoint instanceof Endpoint\Create) {
                return $endpoint->isVisible($context);
            }

            return ! ($actor->isGuest() && $endpoint->getAuthenticated($context));
        } catch (NotAuthenticatedException|PermissionDeniedException) {
            return false;
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * An endpoint behind `admin()` is left out for everyone else. Abilities from
     * `can()` usually need the model, so they are left to the call itself.
     */
    protected function isAdminOnly(Endpoint\Endpoint $endpoint): bool
    {
        // HasAuthorization keeps the flag protected and has no getter.
        return (bool) (fn () => $this->admin ?? false)->call($endpoint);
    }
}
