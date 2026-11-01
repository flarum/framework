<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use Flarum\OAuthProvider\ResourceServer\ResourceRegistry;
use Flarum\OAuthProvider\ResourceServer\TokenValidator;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

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

        $this->container->singleton(ResourceRegistry::class);
        $this->container->singleton(TokenValidator::class);

        // Always the cURL handler: Guzzle's default stack hands requests to the
        // stream handler in some cases (stream option, TLS fallback), which
        // ignores the CURLOPT_RESOLVE pin the metadata document fetch relies on.
        $this->container->bind(MetadataDocumentClients::HTTP_CLIENT, function () {
            return new HttpClient(['handler' => HandlerStack::create(new CurlHandler())]);
        });
    }
}
