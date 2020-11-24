<?php


namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class ModelUrl implements ExtenderInterface
{
    private $modelClass;
    private $slugDrivers = [];
    private $urlGenerator;

    /**
     * @param string $modelClass The ::class attribute of the model you are modifying.
     *                           This model should extend from \Flarum\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add a slug driver.
     *
     * @param string $identifier Identifier for slug driver.
     * @param string $driver ::class attribute of driver class, which must implement Flarum\Http\SlugDriverInterface
     * @return self
     */
    public function addSlugDriver(string $identifier, string $driver) {
        $this->slugDrivers[$identifier] = $driver;

        return $this;
    }

    /**
     * Overrides the url generator for this resource.
     *
     * @param callable|string $callback
     * @return self
     *
     * The callable can be a closure or invokable class, and should accept:
     * - \Flarum\Http\UrlGenerator $urlGenerator: an instance of the URL generator.
     * - \Flarum\Database\AbstractModel $instance: The model instance for which the url is being generated
     * - ...$args: Any additional optional arguments
     *
     * The callable should return:
     * - string $url: A valid URL pointing to the resource instance
     */
    public function setUrlGenerator($callback)
    {
        $this->urlGenerator = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if ($this->urlGenerator) {
            $container->extend('flarum.http.resourceUrlGenerators', function($existingUrlGenerators) use ($container) {
                $existingUrlGenerators[$this->modelClass] = ContainerUtil::wrapCallback($this->urlGenerator, $container);
                return $existingUrlGenerators;
            });
        }
        if ($this->slugDrivers) {
            $container->extend('flarum.http.slugDrivers', function ($existingDrivers) {
                $existingDrivers[$this->modelClass] = array_merge(Arr::get($existingDrivers, $this->modelClass, []), $this->slugDrivers);
                return $existingDrivers;
            });
        }
    }
}
