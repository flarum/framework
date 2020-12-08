<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Exception;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

/**
 * Model visibility scoping allows us to scope queries based on the current user.
 * The main usage of this is only showing model instances that a user is allowed to see.
 *
 * This is done by running a query through a series of "scoper" callbacks, which apply
 * additional `where`s to the query based on the user.
 *
 * Scopers are classified under an ability. Calling `whereVisibleTo` on a query
 * will apply scopers under the `view` ability. Generally, the main `view` scopers
 * can request scoping with other abilities, which provides an entrypoint for extensions
 * to modify some restriction to a query.
 *
 * Scopers registered via `scopeAll` will apply to all queries under a model, regardless
 * of the ability, and will accept the ability name as an additional argument.
 */
class ModelVisibility implements ExtenderInterface
{
    private $modelClass;
    private $scopers = [];
    private $allScopers = [];

    /**
     * @param string $modelClass The ::class attribute of the model you are applying scopers to.
     *                           This model must extend from \Flarum\Database\AbstractModel,
     *                           and use \Flarum\Database\ScopeVisibilityTrait.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;

        if (! method_exists($modelClass, 'registerVisibilityScoper')) {
            throw new Exception("Model $modelClass cannot be visibility scoped as it does not use Flarum\Database\ScopeVisibilityTrait.");
        }
    }

    /**
     * Add a scoper for a given ability.
     *
     * @param callable|string $callback
     * @param string $ability, defaults to 'view'
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $actor
     * - \Illuminate\Database\Eloquent\Builder $query
     *
     * @return self
     */
    public function scope($callback, $ability = 'view')
    {
        $this->scopers[$ability][] = $callback;

        return $this;
    }

    /**
     * Add a scoper scoper that will always run for this model, regardless of requested ability.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $actor
     * - \Illuminate\Database\Eloquent\Builder $query
     * - string $ability
     *
     * @return self
     */
    public function scopeAll($callback)
    {
        $this->allScopers[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->scopers as $ability => $scopers) {
            foreach ($scopers as $scoper) {
                $this->modelClass::registerVisibilityScoper(ContainerUtil::wrapCallback($scoper, $container), $ability);
            }
        }

        foreach ($this->allScopers as $scoper) {
            $this->modelClass::registerVisibilityScoper(ContainerUtil::wrapCallback($scoper, $container));
        }
    }
}
