<?php

namespace Flarum\Extend;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Endpoint\Endpoint;
use Flarum\Api\Resource\Contracts\Collection;
use Flarum\Api\Resource\Contracts\Resource;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Tobyz\JsonApiServer\Resource\AbstractResource;
use Tobyz\JsonApiServer\Schema\Field\Field;
use Tobyz\JsonApiServer\Schema\Sort;

class ApiResource implements ExtenderInterface
{
    private array $endpoints = [];
    private array $removeEndpoints = [];
    private array $endpoint = [];
    private array $fields = [];
    private array $removeFields = [];
    private array $field = [];
    private array $sorts = [];
    private array $removeSorts = [];
    private array $sort = [];

    public function __construct(
        /**
         * Must be a class-string of a class that extends \Flarum\Api\Resource\AbstractResource or \Flarum\Api\Resource\AbstractDatabaseResource.
         *
         * @var class-string<\Flarum\Api\Resource\AbstractResource|\Flarum\Api\Resource\AbstractDatabaseResource>
         */
        private readonly string $resourceClass
    ) {
    }

    /**
     * Add endpoints to the resource.
     *
     * @param callable|class-string $endpoints must be a callable that returns an array of objects that implement \Flarum\Api\Endpoint\Endpoint.
     */
    public function endpoints(callable|string $endpoints): self
    {
        $this->endpoints[] = $endpoints;

        return $this;
    }

    /**
     * Remove endpoints from the resource.
     *
     * @param array $endpoints must be an array of class names of the endpoints.
     * @param callable|class-string|null $condition a callable that returns a boolean or a string that represents whether this should be applied.
     */
    public function removeEndpoints(array $endpoints, callable|string $condition = null): self
    {
        $this->removeEndpoints[] = [$endpoints, $condition];

        return $this;
    }

    /**
     * Modify an endpoint.
     *
     * @param class-string<\Flarum\Api\Endpoint\Endpoint>|array<\Flarum\Api\Endpoint\Endpoint> $endpointClass the class name of the endpoint.
     *                                                                                           or an array of class names of the endpoints.
     * @param callable|class-string $mutator a callable that accepts an endpoint and returns the modified endpoint.
     */
    public function endpoint(string|array $endpointClass, callable|string $mutator): self
    {
        foreach ((array) $endpointClass as $endpointClassItem) {
            $this->endpoint[$endpointClassItem] = $mutator;
        }

        return $this;
    }

    /**
     * Add fields to the resource.
     *
     * @param callable|class-string $fields must be a callable that returns an array of objects that implement \Tobyz\JsonApiServer\Schema\Field.
     */
    public function fields(callable|string $fields): self
    {
        $this->fields[] = $fields;

        return $this;
    }

    /**
     * Remove fields from the resource.
     *
     * @param array $fields must be an array of field names.
     * @param callable|class-string|null $condition a callable that returns a boolean or a string that represents whether this should be applied.
     */
    public function removeFields(array $fields, callable|string $condition = null): self
    {
        $this->removeFields[] = [$fields, $condition];

        return $this;
    }

    /**
     * Modify a field.
     *
     * @param string|string[] $field the name of the field or an array of field names.
     * @param callable|class-string $mutator a callable that accepts a field and returns the modified field.
     */
    public function field(string|array $field, callable|string $mutator): self
    {
        foreach ((array) $field as $fieldItem) {
            $this->field[$fieldItem] = $mutator;
        }

        return $this;
    }

    /**
     * Add sorts to the resource.
     *
     * @param callable|class-string $sorts must be a callable that returns an array of objects that implement \Tobyz\JsonApiServer\Schema\Sort.
     */
    public function sorts(callable|string $sorts): self
    {
        $this->sorts[] = $sorts;

        return $this;
    }

    /**
     * Remove sorts from the resource.
     *
     * @param array $sorts must be an array of sort names.
     * @param callable|class-string|null $condition a callable that returns a boolean or a string that represents whether this should be applied.
     */
    public function removeSorts(array $sorts, callable|string $condition = null): self
    {
        $this->removeSorts[] = [$sorts, $condition];

        return $this;
    }

    /**
     * Modify a sort.
     *
     * @param string|string[] $sort the name of the sort or an array of sort names.
     * @param callable|class-string $mutator a callable that accepts a sort and returns the modified sort.
     */
    public function sort(string|array $sort, callable|string $mutator): self
    {
        foreach ((array) $sort as $sortItem) {
            $this->sort[$sortItem] = $mutator;
        }

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        if (! (new ReflectionClass($this->resourceClass))->isAbstract()) {
            $container->extend('flarum.api.resources', function (array $resources) {
                if (! in_array($this->resourceClass, $resources, true)) {
                    $resources[] = $this->resourceClass;
                }

                return $resources;
            });
        }

        /** @var class-string<\Flarum\Api\Resource\AbstractResource|\Flarum\Api\Resource\AbstractDatabaseResource> $resourceClass */
        $resourceClass = $this->resourceClass;

        $resourceClass::mutateEndpoints(function (array $endpoints, Resource $resource) use ($container): array {
            foreach ($this->endpoints as $newEndpointsCallback) {
                $newEndpointsCallback = ContainerUtil::wrapCallback($newEndpointsCallback, $container);
                $endpoints = array_merge($endpoints, $newEndpointsCallback());
            }

            foreach ($this->removeEndpoints as $removeEndpointClass) {
                [$endpointsToRemove, $condition] = $removeEndpointClass;

                if ($this->isApplicable($condition, $resource, $container)) {
                    $endpoints = array_filter($endpoints, fn (Endpoint $endpoint) => ! in_array($endpoint::class, $endpointsToRemove));
                }
            }

            foreach ($endpoints as $key => $endpoint) {
                $endpointClass = $endpoint::class;

                if (isset($this->endpoint[$endpointClass])) {
                    $mutateEndpoint = ContainerUtil::wrapCallback($this->endpoint[$endpointClass], $container);
                    $endpoint = $mutateEndpoint($endpoint, $resource);

                    if (! $endpoint instanceof Endpoint) {
                        throw new \RuntimeException('The endpoint mutator must return an instance of ' . Endpoint::class);
                    }
                }

                $endpoints[$key] = $endpoint;
            }

            return $endpoints;
        });

        $resourceClass::mutateFields(function (array $fields, Resource $resource) use ($container): array {
            foreach ($this->fields as $newFieldsCallback) {
                $newFieldsCallback = ContainerUtil::wrapCallback($newFieldsCallback, $container);
                $fields = array_merge($fields, $newFieldsCallback());
            }

            foreach ($this->removeFields as $field) {
                [$fieldsToRemove, $condition] = $field;

                if ($this->isApplicable($condition, $resource, $container)) {
                    $fields = array_filter($fields, fn (Field $f) => ! in_array($f->name, $fieldsToRemove));
                }
            }

            foreach ($fields as $key => $field) {
                if (isset($this->field[$field->name])) {
                    $mutateField = ContainerUtil::wrapCallback($this->field[$field->name], $container);
                    $field = $mutateField($field);

                    if (! $field instanceof Field) {
                        throw new \RuntimeException('The field mutator must return an instance of ' . Field::class);
                    }
                }

                $fields[$key] = $field;
            }

            return $fields;
        });

        $resourceClass::mutateSorts(function (array $sorts, Resource $resource) use ($container): array {
            foreach ($this->sorts as $newSortsCallback) {
                $newSortsCallback = ContainerUtil::wrapCallback($newSortsCallback, $container);
                $sorts = array_merge($sorts, $newSortsCallback());
            }

            foreach ($this->removeSorts as $sort) {
                [$sortsToRemove, $condition] = $sort;

                if ($this->isApplicable($condition, $resource, $container)) {
                    $sorts = array_filter($sorts, fn (Sort $s) => ! in_array($s->name, $sortsToRemove));
                }
            }

            foreach ($sorts as $key => $sort) {
                if (isset($this->sort[$sort->name])) {
                    $mutateSort = ContainerUtil::wrapCallback($this->sort[$sort], $container);
                    $sort = $mutateSort($sort);

                    if (! $sort instanceof Sort) {
                        throw new \RuntimeException('The sort mutator must return an instance of ' . Sort::class);
                    }
                }

                $sorts[$key] = $sort;
            }

            return $sorts;
        });
    }

    private function isApplicable(callable|string|null $callback, Resource $resource, Container $container): bool
    {
        if (! isset($callback)) {
            return true;
        }

        $callback = ContainerUtil::wrapCallback($callback, $container);

        return (bool) $callback($resource);
    }
}