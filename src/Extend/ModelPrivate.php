<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

/**
 * Some models, in particular Discussion and CommentPost, are intended to
 * support a "private" mode, wherein they aren't visible unless some
 * criteria is met. This can be used to implement anything from
 * private discussions to post approvals.
 *
 * When a model is saved, any "privacy checkers" registered for it will
 * be run. If any privacy checkers return `true`, the `is_private` field
 * of that model instance will be set to `true`. Otherwise, it will be set to
 * `false`. Accordingly, this is only available for models with an `is_private`
 * field.
 *
 * In Flarum core, the Discussion and CommentPost models come with private support.
 * Core also contains visibility scopers that hide instances of these models
 * with `is_private = true` from queries. Extensions can register custom scopers
 * for these classes with the `viewPrivate` ability to grant access to view some
 * private instances under some conditions.
 */
class ModelPrivate implements ExtenderInterface
{
    private $modelClass;
    private $checkers = [];

    /**
     * @param string $modelClass The ::class attribute of the model you are applying private checkers to.
     *                           This model must have a `is_private` field.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add a model privacy checker.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Flarum\Database\AbstractModel $instance: An instance of the model.
     *
     * It should return `true` if the model instance should be made private.
     *
     * @return self
     */
    public function checker($callback)
    {
        $this->checkers[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        $container->extend('flarum.database.model_private_checkers', function ($originalCheckers) use ($container) {
            foreach ($this->checkers as $checker) {
                $originalCheckers[$this->modelClass][] = ContainerUtil::wrapCallback($checker, $container);
            }

            return $originalCheckers;
        });
    }
}
