<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\JsonApiResponse;
use Flarum\Api\Serializer\AbstractSerializer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\ElementInterface;
use Tobscure\JsonApi\Parameters;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractSerializeController implements RequestHandlerInterface
{
    /**
     * The name of the serializer class to output results with.
     *
     * @var class-string<AbstractSerializer>|null
     */
    public ?string $serializer;

    /**
     * The relationships that are included by default.
     *
     * @var string[]
     */
    public array $include = [];

    /**
     * The relationships that are available to be included.
     *
     * @var string[]
     */
    public array $optionalInclude = [];

    /**
     * The maximum number of records that can be requested.
     */
    public int $maxLimit = 50;

    /**
     * The number of records included by default.
     */
    public int $limit = 20;

    /**
     * The fields that are available to be sorted by.
     *
     * @var string[]
     */
    public array $sortFields = [];

    /**
     * The default sort field and order to use.
     *
     * @var array<string, string>|null
     */
    public ?array $sort = null;

    protected static Container $container;

    /**
     * @var array<class-string<self>, callable[]>
     */
    protected static array $beforeDataCallbacks = [];

    /**
     * @var array<class-string<self>, callable[]>
     */
    protected static array $beforeSerializationCallbacks = [];

    /**
     * @var string[][]
     */
    protected static array $loadRelations = [];

    /**
     * @var array<string, callable>
     */
    protected static array $loadRelationCallables = [];

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $document = new Document;

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$beforeDataCallbacks[$class])) {
                foreach (static::$beforeDataCallbacks[$class] as $callback) {
                    $callback($this);
                }
            }
        }

        $data = $this->data($request, $document);

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$beforeSerializationCallbacks[$class])) {
                foreach (static::$beforeSerializationCallbacks[$class] as $callback) {
                    $callback($this, $data, $request, $document);
                }
            }
        }

        if (empty($this->serializer)) {
            throw new InvalidArgumentException('Serializer required for controller: '.static::class);
        }

        $serializer = static::$container->make($this->serializer);
        $serializer->setRequest($request);

        $element = $this->createElement($data, $serializer)
            ->with($this->extractInclude($request))
            ->fields($this->extractFields($request));

        $document->setData($element);

        return new JsonApiResponse($document);
    }

    /**
     * Get the data to be serialized and assigned to the response document.
     */
    abstract protected function data(ServerRequestInterface $request, Document $document): mixed;

    /**
     * Create a PHP JSON-API Element for output in the document.
     */
    abstract protected function createElement(mixed $data, SerializerInterface $serializer): ElementInterface;

    /**
     * Returns the relations to load added by extenders.
     *
     * @return string[]
     */
    protected function getRelationsToLoad(Collection $models): array
    {
        $addedRelations = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelations[$class])) {
                $addedRelations = array_merge($addedRelations, static::$loadRelations[$class]);
            }
        }

        return $addedRelations;
    }

    /**
     * Returns the relation callables to load added by extenders.
     *
     * @return array<string, callable>
     */
    protected function getRelationCallablesToLoad(Collection $models): array
    {
        $addedRelationCallables = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelationCallables[$class])) {
                $addedRelationCallables = array_merge($addedRelationCallables, static::$loadRelationCallables[$class]);
            }
        }

        return $addedRelationCallables;
    }

    /**
     * Eager loads the required relationships.
     */
    protected function loadRelations(Collection $models, array $relations, ServerRequestInterface $request = null): void
    {
        $addedRelations = $this->getRelationsToLoad($models);
        $addedRelationCallables = $this->getRelationCallablesToLoad($models);

        foreach ($addedRelationCallables as $name => $relation) {
            $addedRelations[] = $name;
        }

        if (! empty($addedRelations)) {
            usort($addedRelations, function ($a, $b) {
                return substr_count($a, '.') - substr_count($b, '.');
            });

            foreach ($addedRelations as $relation) {
                if (str_contains($relation, '.')) {
                    $parentRelation = Str::beforeLast($relation, '.');

                    if (! in_array($parentRelation, $relations, true)) {
                        continue;
                    }
                }

                $relations[] = $relation;
            }
        }

        if (! empty($relations)) {
            $relations = array_unique($relations);
        }

        $callableRelations = [];
        $nonCallableRelations = [];

        foreach ($relations as $relation) {
            if (isset($addedRelationCallables[$relation])) {
                $load = $addedRelationCallables[$relation];

                $callableRelations[$relation] = function ($query) use ($load, $request, $relations) {
                    $load($query, $request, $relations);
                };
            } else {
                $nonCallableRelations[] = $relation;
            }
        }

        if (! empty($callableRelations)) {
            $models->loadMissing($callableRelations);
        }

        if (! empty($nonCallableRelations)) {
            $models->loadMissing($nonCallableRelations);
        }
    }

    /**
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractInclude(ServerRequestInterface $request): array
    {
        $available = array_merge($this->include, $this->optionalInclude);

        return $this->buildParameters($request)->getInclude($available) ?: $this->include;
    }

    protected function extractFields(ServerRequestInterface $request): array
    {
        return $this->buildParameters($request)->getFields();
    }

    /**
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractSort(ServerRequestInterface $request): ?array
    {
        return $this->buildParameters($request)->getSort($this->sortFields) ?: $this->sort;
    }

    /**
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractOffset(ServerRequestInterface $request): int
    {
        return (int) $this->buildParameters($request)->getOffset($this->extractLimit($request)) ?: 0;
    }

    /**
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractLimit(ServerRequestInterface $request): int
    {
        return (int) $this->buildParameters($request)->getLimit($this->maxLimit) ?: $this->limit;
    }

    protected function extractFilter(ServerRequestInterface $request): array
    {
        return $this->buildParameters($request)->getFilter() ?: [];
    }

    protected function buildParameters(ServerRequestInterface $request): Parameters
    {
        return new Parameters($request->getQueryParams());
    }

    protected function sortIsDefault(ServerRequestInterface $request): bool
    {
        return ! Arr::get($request->getQueryParams(), 'sort');
    }

    /**
     * Set the serializer that will serialize data for the endpoint.
     */
    public function setSerializer(string $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * Include the given relationship by default.
     */
    public function addInclude(array|string $name): void
    {
        $this->include = array_merge($this->include, (array) $name);
    }

    /**
     * Don't include the given relationship by default.
     */
    public function removeInclude(array|string $name): void
    {
        $this->include = array_diff($this->include, (array) $name);
    }

    /**
     * Make the given relationship available for inclusion.
     */
    public function addOptionalInclude(array|string $name): void
    {
        $this->optionalInclude = array_merge($this->optionalInclude, (array) $name);
    }

    /**
     * Don't allow the given relationship to be included.
     */
    public function removeOptionalInclude(array|string $name): void
    {
        $this->optionalInclude = array_diff($this->optionalInclude, (array) $name);
    }

    /**
     * Set the default number of results.
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Set the maximum number of results.
     */
    public function setMaxLimit(int $max): void
    {
        $this->maxLimit = $max;
    }

    /**
     * Allow sorting results by the given field.
     */
    public function addSortField(array|string $field): void
    {
        $this->sortFields = array_merge($this->sortFields, (array) $field);
    }

    /**
     * Disallow sorting results by the given field.
     */
    public function removeSortField(array|string $field): void
    {
        $this->sortFields = array_diff($this->sortFields, (array) $field);
    }

    /**
     * Set the default sort order for the results.
     */
    public function setSort(array $sort): void
    {
        $this->sort = $sort;
    }

    public static function getContainer(): Container
    {
        return static::$container;
    }

    /**
     * @internal
     */
    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    /**
     * @internal
     */
    public static function addDataPreparationCallback(string $controllerClass, callable $callback): void
    {
        if (! isset(static::$beforeDataCallbacks[$controllerClass])) {
            static::$beforeDataCallbacks[$controllerClass] = [];
        }

        static::$beforeDataCallbacks[$controllerClass][] = $callback;
    }

    /**
     * @internal
     */
    public static function addSerializationPreparationCallback(string $controllerClass, callable $callback): void
    {
        if (! isset(static::$beforeSerializationCallbacks[$controllerClass])) {
            static::$beforeSerializationCallbacks[$controllerClass] = [];
        }

        static::$beforeSerializationCallbacks[$controllerClass][] = $callback;
    }

    /**
     * @internal
     */
    public static function setLoadRelations(string $controllerClass, array $relations): void
    {
        if (! isset(static::$loadRelations[$controllerClass])) {
            static::$loadRelations[$controllerClass] = [];
        }

        static::$loadRelations[$controllerClass] = array_merge(static::$loadRelations[$controllerClass], $relations);
    }

    /**
     * @internal
     */
    public static function setLoadRelationCallables(string $controllerClass, array $relations): void
    {
        if (! isset(static::$loadRelationCallables[$controllerClass])) {
            static::$loadRelationCallables[$controllerClass] = [];
        }

        static::$loadRelationCallables[$controllerClass] = array_merge(static::$loadRelationCallables[$controllerClass], $relations);
    }
}
