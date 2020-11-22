<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Policy implements ExtenderInterface
{
    private $modelClass;
    private $addPolicies = [];

    /**
     * @param string $modelClass The ::class attribute of the model you are applying policies to.
     *                           This model should extend from \Flarum\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add a custom policy.
     *
     * @param string $policy ::class attribute of policy class, which must extend Flarum\User\AbstractPolicy
     */
    public function add($policy)
    {
        $this->addPolicies[] = $policy;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.policies', function ($existingPolicies) {
            if (! array_key_exists($this->modelClass, $existingPolicies)) {
                $existingPolicies[$this->modelClass] = [];
            }
            foreach ($this->addPolicies as $policy) {
                $existingPolicies[$this->modelClass][] = $policy;
            }

            return $existingPolicies;
        });
    }
}
