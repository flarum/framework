<?php

namespace Flarum\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use Psr\Http\Message\ServerRequestInterface as Request;

class Router
{
    /**
     * @var \FastRoute\DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var \FastRoute\RouteParser
     */
    protected $routeParser;

    /**
     * @var string
     */
    protected $currentRequestName;

    /**
     * @var array
     */
    protected $currentRequestParameters;

    /**
     * @var array
     */
    protected $reverse = [];

    /**
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;


    public function __construct()
    {
        $this->routeParser = new RouteParser\Std;
        $this->dataGenerator = new DataGenerator\GroupCountBased;
    }

    public function get($path, $name, $handler)
    {
        return $this->addRoute('GET', $path, $name, $handler);
    }

    public function post($path, $name, $handler)
    {
        return $this->addRoute('POST', $path, $name, $handler);
    }

    public function put($path, $name, $handler)
    {
        return $this->addRoute('PUT', $path, $name, $handler);
    }

    public function delete($path, $name, $handler)
    {
        return $this->addRoute('DELETE', $path, $name, $handler);
    }

    public function addRoute($method, $path, $name, $handler)
    {
        $routeData = $this->routeParser->parse($path);
        $this->dataGenerator->addRoute($method, $routeData, $handler);

        $routeData['method'] = $method;
        $this->reverse[$name] = $routeData;

        return $this;
    }

    public function getPath($name, $parameters = [])
    {
        $parts = $this->reverse[$name];

        $path = implode('', array_map(function ($part) use ($parameters) {
            if (is_array($part)) {
                $part = $parameters[$part[0]];
            }
            return $part;
        }, $parts));

        $path = '/' . ltrim($path, '/');
        return $path;
    }

    public function getCurrentPath()
    {
        $name = $this->currentRequestName;
        $parameters = $this->currentRequestParameters;

        return $this->getPath($name, $parameters);
    }

    public function getMethod($handler)
    {
        return array_get($this->reverse, $handler . '.method', '');
    }

    public function dispatch(Request $request)
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        $routeInfo = $this->getDispatcher()->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \Exception('404 Not Found');
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new \Exception('405 Method Not Allowed');
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $parameters = $routeInfo[2];

                return $handler($request, $parameters);
        }
    }

    protected function getDispatcher()
    {
        if (! isset($this->dispatcher)) {
            $this->dispatcher = new Dispatcher\GroupCountBased($this->dataGenerator->getData());
        }

        return $this->dispatcher;
    }
}
