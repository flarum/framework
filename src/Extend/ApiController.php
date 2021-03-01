<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ApiController implements ExtenderInterface
{
    private $controllerClass;
    private $beforeDataCallbacks = [];
    private $beforeSerializationCallbacks = [];
    private $serializer;
    private $addIncludes = [];
    private $removeIncludes = [];
    private $addOptionalIncludes = [];
    private $removeOptionalIncludes = [];
    private $limit;
    private $maxLimit;
    private $addSortFields = [];
    private $removeSortFields = [];
    private $sort;

    /**
     * @param string $controllerClass The ::class attribute of the controller you are modifying.
     *                                This controller should extend from \Flarum\Api\Controller\AbstractSerializeController.
     */
    public function __construct(string $controllerClass)
    {
        $this->controllerClass = $controllerClass;
    }

    /**
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     *
     * @return self
     */
    public function prepareDataQuery($callback)
    {
        $this->beforeDataCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param callable|string $callback
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
    public function prepareDataForSerialization($callback)
    {
        $this->beforeSerializationCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the serializer that will serialize data for the endpoint.
     *
     * @param string $serializerClass
     * @param callable|string|null $callback
     * @return self
     */
    public function setSerializer(string $serializerClass, $callback = null)
    {
        $this->serializer = [$serializerClass, $callback];

        return $this;
    }

    /**
     * Include the given relationship by default.
     *
     * @param string|array $name
     * @param callable|string|null $callback
     * @return self
     */
    public function addInclude($name, $callback = null)
    {
        $this->addIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Don't include the given relationship by default.
     *
     * @param string|array $name
     * @param callable|string|null $callback
     * @return self
     */
    public function removeInclude($name, $callback = null)
    {
        $this->removeIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Make the given relationship available for inclusion.
     *
     * @param string|array $name
     * @param callable|string|null $callback
     * @return self
     */
    public function addOptionalInclude($name, $callback = null)
    {
        $this->addOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Don't allow the given relationship to be included.
     *
     * @param string|array $name
     * @param callable|string|null $callback
     * @return self
     */
    public function removeOptionalInclude($name, $callback = null)
    {
        $this->removeOptionalIncludes[] = [$name, $callback];

        return $this;
    }

    /**
     * Set the default number of results.
     *
     * @param int $limit
     * @param callable|string|null $callback
     * @return self
     */
    public function setLimit(int $limit, $callback = null)
    {
        $this->limit = [$limit, $callback];

        return $this;
    }

    /**
     * Set the maximum number of results.
     *
     * @param int $max
     * @param callable|string|null $callback
     * @return self
     */
    public function setMaxLimit(int $max, $callback = null)
    {
        $this->maxLimit = [$max, $callback];

        return $this;
    }

    /**
     * Allow sorting results by the given field.
     *
     * @param string|array $field
     * @param callable|string|null $callback
     * @return self
     */
    public function addSortField($field, $callback = null)
    {
        $this->addSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * Disallow sorting results by the given field.
     *
     * @param string|array $field
     * @param callable|string|null $callback
     * @return self
     */
    public function removeSortField($field, $callback = null)
    {
        $this->removeSortFields[] = [$field, $callback];

        return $this;
    }

    /**
     * Set the default sort order for the results.
     *
     * @param array $sort
     * @param callable|string|null $callback
     * @return self
     */
    public function setSort(array $sort, $callback = null)
    {
        $this->sort = [$sort, $callback];

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
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
    }

    /**
     * @param callable|string|null $callback
     * @param AbstractSerializeController $controller
     * @param Container $container
     * @return bool
     */
    private function isApplicable($callback, AbstractSerializeController $controller, Container $container)
    {
        if (! isset($callback)) {
            return true;
        }

        $callback = ContainerUtil::wrapCallback($callback, $container);

        return (bool) $callback($controller);
    }
}
