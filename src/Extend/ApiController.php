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
use Illuminate\Contracts\Container\Container;

class ApiController implements ExtenderInterface
{
    private $controllerClass;
    private $beforeDataCallbacks = [];
    private $beforeSerializationCallbacks = [];

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
     * @param $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $controller: An instance of this controller.
     * - $data: An array of data.
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

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->beforeDataCallbacks as $beforeDataCallback) {
            AbstractSerializeController::addDataPreparationCallback($this->controllerClass, $beforeDataCallback);
        }

        foreach ($this->beforeSerializationCallbacks as $beforeSerializationCallback) {
            AbstractSerializeController::addSerializationPreparationCallback($this->controllerClass, $beforeSerializationCallback);
        }
    }
}
