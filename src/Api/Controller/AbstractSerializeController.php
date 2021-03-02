<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Parameters;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractSerializeController implements RequestHandlerInterface
{
    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public $serializer;

    /**
     * The relationships that are included by default.
     *
     * @var array
     */
    public $include = [];

    /**
     * The relationships that are available to be included.
     *
     * @var array
     */
    public $optionalInclude = [];

    /**
     * The maximum number of records that can be requested.
     *
     * @var int
     */
    public $maxLimit = 50;

    /**
     * The number of records included by default.
     *
     * @var int
     */
    public $limit = 20;

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public $sortFields = [];

    /**
     * The default sort field and order to user.
     *
     * @var array|null
     */
    public $sort;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var array
     */
    protected static $beforeDataCallbacks = [];

    /**
     * @var array
     */
    protected static $beforeSerializationCallbacks = [];

    /**
     * {@inheritdoc}
     */
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
     *
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     */
    abstract protected function data(ServerRequestInterface $request, Document $document);

    /**
     * Create a PHP JSON-API Element for output in the document.
     *
     * @param mixed $data
     * @param SerializerInterface $serializer
     * @return \Tobscure\JsonApi\ElementInterface
     */
    abstract protected function createElement($data, SerializerInterface $serializer);

    /**
     * @param ServerRequestInterface $request
     * @return array
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractInclude(ServerRequestInterface $request)
    {
        $available = array_merge($this->include, $this->optionalInclude);

        return $this->buildParameters($request)->getInclude($available) ?: $this->include;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function extractFields(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getFields();
    }

    /**
     * @param ServerRequestInterface $request
     * @return array|null
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractSort(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getSort($this->sortFields) ?: $this->sort;
    }

    /**
     * @param ServerRequestInterface $request
     * @return int
     * @throws \Tobscure\JsonApi\Exception\InvalidParameterException
     */
    protected function extractOffset(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getOffset($this->extractLimit($request)) ?: 0;
    }

    /**
     * @param ServerRequestInterface $request
     * @return int
     */
    protected function extractLimit(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getLimit($this->maxLimit) ?: $this->limit;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function extractFilter(ServerRequestInterface $request)
    {
        return $this->buildParameters($request)->getFilter() ?: [];
    }

    /**
     * @param ServerRequestInterface $request
     * @return Parameters
     */
    protected function buildParameters(ServerRequestInterface $request)
    {
        return new Parameters($request->getQueryParams());
    }

    /**
     * Set the serializer that will serialize data for the endpoint.
     *
     * @param string $serializer
     */
    public function setSerializer(string $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Include the given relationship by default.
     *
     * @param string|array $name
     */
    public function addInclude($name)
    {
        $this->include = array_merge($this->include, (array) $name);
    }

    /**
     * Don't include the given relationship by default.
     *
     * @param string|array $name
     */
    public function removeInclude($name)
    {
        $this->include = array_diff($this->include, (array) $name);
    }

    /**
     * Make the given relationship available for inclusion.
     *
     * @param string|array $name
     */
    public function addOptionalInclude($name)
    {
        $this->optionalInclude = array_merge($this->optionalInclude, (array) $name);
    }

    /**
     * Don't allow the given relationship to be included.
     *
     * @param string|array $name
     */
    public function removeOptionalInclude($name)
    {
        $this->optionalInclude = array_diff($this->optionalInclude, (array) $name);
    }

    /**
     * Set the default number of results.
     *
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * Set the maximum number of results.
     *
     * @param int $max
     */
    public function setMaxLimit(int $max)
    {
        $this->maxLimit = $max;
    }

    /**
     * Allow sorting results by the given field.
     *
     * @param string|array $field
     */
    public function addSortField($field)
    {
        $this->sortFields = array_merge($this->sortFields, (array) $field);
    }

    /**
     * Disallow sorting results by the given field.
     *
     * @param string|array $field
     */
    public function removeSortField($field)
    {
        $this->sortFields = array_diff($this->sortFields, (array) $field);
    }

    /**
     * Set the default sort order for the results.
     *
     * @param array $sort
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * @param Container $container
     */
    public static function setContainer(Container $container)
    {
        static::$container = $container;
    }

    /**
     * @param string $controllerClass
     * @param callable $callback
     */
    public static function addDataPreparationCallback(string $controllerClass, callable $callback)
    {
        if (! isset(static::$beforeDataCallbacks[$controllerClass])) {
            static::$beforeDataCallbacks[$controllerClass] = [];
        }

        static::$beforeDataCallbacks[$controllerClass][] = $callback;
    }

    /**
     * @param string $controllerClass
     * @param callable $callback
     */
    public static function addSerializationPreparationCallback(string $controllerClass, callable $callback)
    {
        if (! isset(static::$beforeSerializationCallbacks[$controllerClass])) {
            static::$beforeSerializationCallbacks[$controllerClass] = [];
        }

        static::$beforeSerializationCallbacks[$controllerClass][] = $callback;
    }
}
