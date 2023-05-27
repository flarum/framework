<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ApiController implements ExtenderInterface
{
    private array $beforeDataCallbacks = [];
    private array $beforeSerializationCallbacks = [];
    private array $serializer;
    private array $addIncludes = [];
    private array $removeIncludes = [];
    private array $addOptionalIncludes = [];
    private array $removeOptionalIncludes = [];
    private array $limit;
    private array $maxLimit;
    private array $addSortFields = [];
    private array $removeSortFields = [];
    private array $sort;
    private array $load = [];
    private array $loadCallables = [];

    /**
     * @param class-string<AbstractSerializeController> $controllerClass: The ::class attribute of the controller you are modifying.
     *                                This controller should extend from \Flarum\Api\Controller\AbstractSerializeController.
     */
    public function __construct(
        private readonly string $controllerClass
    ) {
    }

    /**
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): void)|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * @return self
     */
    public function prepareDataQuery(callable|string $callback): self
    {
        $this->beforeDataCallbacks[] = $callback;

        return $this;
    }

    /**
     * @template S of AbstractSerializeController
     * @param (callable(S $controller, mixed $data, ServerRequestInterface $request, Document $document): array)|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     * - $data: Mixed, can be an array of data or an object (like an instance of Collection or AbstractModel).
     * - $request: An instance of \Psr\Http\Message\ServerRequestInterface.
     * - $document: An instance of \Tobscure\JsonApi\Document.
     *
     * The callable should return:
     * - An array of additional data to merge with the existing array.
     *   Or a modified $data array.
     *
     * @return self
     */
    public function prepareDataForSerialization(callable|string $callback): self
    {
        $this->beforeSerializationCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the serializer that will serialize data for the endpoint.
     *
     * @template S of AbstractSerializeController
     * @param class-string<AbstractSerializer> $serializerClass: The ::class attribute of the serializer.
     * @param (callable(S $controller): bool)|string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function setSerializer(string $serializerClass, callable|string $callback = null): self
    {
        $this->serializer = [$serializerClass, $callback];

        return $this;
    }

    /**
     * Include the given relationship by default.
     *
     * @template S of AbstractSerializeController
     * @param array|string $name: The name of the relation.
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function addInclude(array|string $name, callable|string $callback = null): self
    {
        $this->addIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Don't include the given relationship by default.
     *
     * @param array|string $name: The name of the relation.
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function removeInclude(array|string $name, callable|string $callback = null): self
    {
        $this->removeIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Make the given relationship available for inclusion.
     *
     * @param array|string $name: The name of the relation.
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function addOptionalInclude(array|string $name, callable|string $callback = null): self
    {
        $this->addOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Don't allow the given relationship to be included.
     *
     * @param array|string $name: The name of the relation.
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function removeOptionalInclude(array|string $name, callable|string $callback = null): self
    {
        $this->removeOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Set the default number of results.
     *
     * @param int $limit
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function setLimit(int $limit, callable|string $callback = null): self
    {
        $this->limit = [$limit, $callback];

        return $this;
    }

    /**
     * Set the maximum number of results.
     *
     * @param int $max
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function setMaxLimit(int $max, callable|string $callback = null): self
    {
        $this->maxLimit = [$max, $callback];

        return $this;
    }

    /**
     * Allow sorting results by the given field.
     *
     * @param array|string $field
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function addSortField(array|string $field, callable|string $callback = null): self
    {
        $this->addSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * Disallow sorting results by the given field.
     *
     * @param array|string $field
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function removeSortField(array|string $field, callable|string $callback = null): self
    {
        $this->removeSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * Set the default sort order for the results.
     *
     * @param array $sort
     * @template S of AbstractSerializeController
     * @param (callable(S $controller): bool)|class-string|null $callback
     *
     * The optional callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * The callable should return:
     * - A boolean value to determine if this applies.
     *
     * @return self
     */
    public function setSort(array $sort, callable|string $callback = null): self
    {
        $this->sort = [$sort, $callback];

        return $this;
    }

    /**
     * Eager loads relationships needed for serializer logic.
     *
     * First level relationships will be loaded regardless of whether they are included in the response.
     * Sub-level relationships will only be loaded if the upper level was included or manually loaded.
     *
     * @example If a relationship such as: 'relation.subRelation' is specified,
     * it will only be loaded if 'relation' is or has been loaded.
     * To force load the relationship, both levels have to be specified,
     * example: ['relation', 'relation.subRelation'].
     *
     * @param string|string[] $relations
     * @return self
     */
    public function load(array|string $relations): self
    {
        $this->load = array_merge($this->load, array_map('strval', (array) $relations));

        return $this;
    }

    /**
     * Allows loading a relationship with additional query modification.
     *
     * @param string $relation: Relationship name, see load method description.
     * @template R of Relation
     * @param (callable(Builder|R, \Psr\Http\Message\ServerRequestInterface|null, array): void) $callback
     *
     * The callback to modify the query, should accept:
     * - \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query: A query object.
     * - \Psr\Http\Message\ServerRequestInterface|null $request: An instance of the request.
     * - array $relations: An array of relations that are to be loaded.
     *
     * @return self
     */
    public function loadWhere(string $relation, callable $callback): self
    {
        $this->loadCallables = array_merge($this->loadCallables, [$relation => $callback]);

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $this->beforeDataCallbacks[] = function (AbstractSerializeController $controller) use ($container) {
            if (isset($this->serializer) && $this->isApplicable($this->serializer[1], $controller, $container)) {
                $controller->setSerializer($this->serializer[0]);
            }

            foreach ($this->addIncludes as $addingInclude) {
                if ($this->isApplicable($addingInclude[1], $controller, $container)) {
                    $controller->addInclude($addingInclude[0]);
                }
            }

            foreach ($this->removeIncludes as $removingInclude) {
                if ($this->isApplicable($removingInclude[1], $controller, $container)) {
                    $controller->removeInclude($removingInclude[0]);
                }
            }

            foreach ($this->addOptionalIncludes as $addingOptionalInclude) {
                if ($this->isApplicable($addingOptionalInclude[1], $controller, $container)) {
                    $controller->addOptionalInclude($addingOptionalInclude[0]);
                }
            }

            foreach ($this->removeOptionalIncludes as $removingOptionalInclude) {
                if ($this->isApplicable($removingOptionalInclude[1], $controller, $container)) {
                    $controller->removeOptionalInclude($removingOptionalInclude[0]);
                }
            }

            foreach ($this->addSortFields as $addingSortField) {
                if ($this->isApplicable($addingSortField[1], $controller, $container)) {
                    $controller->addSortField($addingSortField[0]);
                }
            }

            foreach ($this->removeSortFields as $removingSortField) {
                if ($this->isApplicable($removingSortField[1], $controller, $container)) {
                    $controller->removeSortField($removingSortField[0]);
                }
            }

            if (isset($this->limit) && $this->isApplicable($this->limit[1], $controller, $container)) {
                $controller->setLimit($this->limit[0]);
            }

            if (isset($this->maxLimit) && $this->isApplicable($this->maxLimit[1], $controller, $container)) {
                $controller->setMaxLimit($this->maxLimit[0]);
            }

            if (isset($this->sort) && $this->isApplicable($this->sort[1], $controller, $container)) {
                $controller->setSort($this->sort[0]);
            }
        };

        foreach ($this->beforeDataCallbacks as $beforeDataCallback) {
            $beforeDataCallback = ContainerUtil::wrapCallback($beforeDataCallback, $container);
            AbstractSerializeController::addDataPreparationCallback($this->controllerClass, $beforeDataCallback);
        }

        foreach ($this->beforeSerializationCallbacks as $beforeSerializationCallback) {
            $beforeSerializationCallback = ContainerUtil::wrapCallback($beforeSerializationCallback, $container);
            AbstractSerializeController::addSerializationPreparationCallback($this->controllerClass, $beforeSerializationCallback);
        }

        AbstractSerializeController::setLoadRelations($this->controllerClass, $this->load);
        AbstractSerializeController::setLoadRelationCallables($this->controllerClass, $this->loadCallables);
    }

    private function isApplicable(callable|string|null $callback, AbstractSerializeController $controller, Container $container): bool
    {
        if (! isset($callback)) {
            return true;
        }

        $callback = ContainerUtil::wrapCallback($callback, $container);

        return (bool) $callback($controller);
    }
}
