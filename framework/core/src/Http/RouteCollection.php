<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;

class RouteCollection
{
    /**
     * @var array
     */
    protected $reverse = [];

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var RouteParser
     */
    protected $routeParser;

    public function __construct()
    {
        $this->dataGenerator = new DataGenerator\GroupCountBased;
        $this->routeParser = new RouteParser\Std;
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

    public function patch($path, $name, $handler)
    {
        return $this->addRoute('PATCH', $path, $name, $handler);
    }

    public function delete($path, $name, $handler)
    {
        return $this->addRoute('DELETE', $path, $name, $handler);
    }

    public function addRoute($method, $path, $name, $handler)
    {
        $routeDatas = $this->routeParser->parse($path);

        foreach ($routeDatas as $routeData) {
            $this->dataGenerator->addRoute($method, $routeData, $handler);
        }

        $this->reverse[$name] = $routeDatas;

        return $this;
    }

    public function getRouteData()
    {
        return $this->dataGenerator->getData();
    }

    public function getPath($name, $parameters = [])
    {
        $parts = $this->reverse[$name][0];

        $path = implode('', array_map(function ($part) use ($parameters) {
            if (is_array($part)) {
                $part = $parameters[$part[0]];
            }
            return $part;
        }, $parts));

        $path = '/' . ltrim($path, '/');
        return $path;
    }
}
