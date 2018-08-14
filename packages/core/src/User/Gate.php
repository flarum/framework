<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * @author Taylor Otwell
 */
class Gate implements GateContract
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * @param  Container $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [], array $policies = [], array $beforeCallbacks = [])
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->beforeCallbacks = $beforeCallbacks;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback) && str_contains($callback, '@')) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            list($class, $method) = explode('@', $callback);

            return call_user_func_array([$this->resolvePolicy($class), $method], func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($ability, $arguments = [])
    {
        if (! $user = $this->resolveUser()) {
            return false;
        }

        $arguments = is_array($arguments) ? $arguments : [$arguments];

        if (! is_null($result = $this->callBeforeCallbacks($user, $ability, $arguments))) {
            return $result;
        }

        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return call_user_func_array($callback, array_merge([$user], $arguments));
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool|void
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        $arguments = array_merge([$user, $ability], $arguments);

        foreach ($this->beforeCallbacks as $before) {
            if (! is_null($result = call_user_func_array($before, $arguments))) {
                return $result;
            }
        }
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if ($this->firstArgumentCorrespondsToPolicy($arguments)) {
            return $this->resolvePolicyCallback($user, $ability, $arguments);
        } elseif (isset($this->abilities[$ability])) {
            return $this->abilities[$ability];
        } else {
            return function () {
                return false;
            };
        }
    }

    /**
     * Determine if the first argument in the array corresponds to a policy.
     *
     * @param  array  $arguments
     * @return bool
     */
    protected function firstArgumentCorrespondsToPolicy(array $arguments)
    {
        if (! isset($arguments[0])) {
            return false;
        }

        if (is_object($arguments[0])) {
            return isset($this->policies[get_class($arguments[0])]);
        }

        return is_string($arguments[0]) && isset($this->policies[$arguments[0]]);
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments)
    {
        return function () use ($user, $ability, $arguments) {
            $instance = $this->getPolicyFor($arguments[0]);

            if (method_exists($instance, 'before')) {
                // We will prepend the user and ability onto the arguments so that the before
                // callback can determine which ability is being called. Then we will call
                // into the policy before methods with the arguments and get the result.
                $beforeArguments = array_merge([$user, $ability], $arguments);

                $result = call_user_func_array([$instance, 'before'], $beforeArguments);

                // If we received a non-null result from the before method, we will return it
                // as the result of a check. This allows developers to override the checks
                // in the policy and return a result for all rules defined in the class.
                if (! is_null($result)) {
                    return $result;
                }
            }

            if (! is_callable([$instance, $ability])) {
                return false;
            }

            return call_user_func_array([$instance, $ability], array_merge([$user], $arguments));
        };
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! isset($this->policies[$class])) {
            throw new InvalidArgumentException("Policy not defined for [{$class}].");
        }

        return $this->resolvePolicy($this->policies[$class]);
    }

    /**
     * Build a policy class instance of the given type.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Get a guard instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        return new static(
            $this->container,
            function () use ($user) {
                return $user;
            },
            $this->abilities,
            $this->policies,
            $this->beforeCallbacks
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param  callable $callback
     * @return GateContract
     */
    public function after(callable $callback)
    {
        // TODO: Implement after() method.
    }

    /**
     * Determine if any one of the given abilities should be granted for the current user.
     *
     * @param  iterable|string $abilities
     * @param  array|mixed $arguments
     * @return bool
     */
    public function any($abilities, $arguments = [])
    {
        // TODO: Implement any() method.
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string $ability
     * @param  array|mixed $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        // TODO: Implement authorize() method.
    }

    /**
     * Get all of the defined abilities.
     *
     * @return array
     */
    public function abilities()
    {
        // TODO: Implement abilities() method.
    }
}
