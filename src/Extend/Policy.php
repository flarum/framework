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
    protected $addPolicies = [];

    /**
     * Add a custom policy
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
            return array_merge($existingPolicies, $this->addPolicies);
        });
    }
}
