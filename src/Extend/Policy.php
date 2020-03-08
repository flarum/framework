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
     * Get the result of an API request to show a user.
     *
     * @param string $identifier Identifier for mail driver. E.g. 'smtp' for SmtpDriver
     * @param string $driver ::class attribute of driver class, which must implement Flarum\Mail\DriverInterface
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
