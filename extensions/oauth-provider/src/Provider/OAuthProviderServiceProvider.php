<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\OAuthProvider\Scope\ScopeRegistry;

class OAuthProviderServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ScopeRegistry::class, function () {
            $registry = new ScopeRegistry();

            $registry->register('openid', 'Identify you to the application');
            $registry->register('profile', 'Access your public profile (username, display name, avatar)');
            $registry->register('email', 'Access your email address');

            return $registry;
        });
    }
}
