<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * @internal
 */
trait HasContainer
{
    /**
     * @var ?\Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(ContainerContract $container): void
    {
        $this->container = $container;
    }

    /**
     * Returns the current broker.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer(): ContainerContract
    {
        return $this->container ?? Container::getInstance();
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     */
    public function resolve(string $abstract)
    {
        $concrete = null;

        try {
            $concrete = $this->getContainer()
                ->make($abstract);
        } catch (ReflectionException $exception) {
            // ..
        } catch (BindingResolutionException $exception) {
            // ..
        } catch (NotFoundExceptionInterface $exception) {
            // ..
        }

        return $concrete;
    }
}
