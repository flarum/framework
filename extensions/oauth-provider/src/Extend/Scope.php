<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Illuminate\Contracts\Container\Container;

/**
 * Extender for registering OAuth 2 scopes.
 *
 * Usage in an extension's extend.php:
 *
 *   (new \Flarum\OAuthProvider\Extend\Scope('email', 'Read your email address'))
 */
class Scope implements ExtenderInterface
{
    public function __construct(
        protected string $identifier,
        protected string $description,
    ) {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend(ScopeRegistry::class, function (ScopeRegistry $registry) {
            $registry->register($this->identifier, $this->description);

            return $registry;
        });
    }
}
