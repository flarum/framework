<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\IdWithTransliteratedSlugDriver;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\FrontendFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Flarum\User\UsernameSlugDriver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('flarum.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->container->bind(Middleware\CheckCsrfToken::class, function ($container) {
            return new Middleware\CheckCsrfToken($container->make('flarum.http.csrfExemptPaths'));
        });

        $this->container->singleton('flarum.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithTransliteratedSlugDriver::class
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class
                ],
            ];
        });

        $this->container->singleton('flarum.http.selectedSlugDrivers', function () {
            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($this->container->make('flarum.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverKey = $settings->get("slug_driver_$resourceClass", 'default');

                $driverClass = Arr::get($resourceDrivers, $driverKey, $resourceDrivers['default']);

                $compiledDrivers[$resourceClass] = $this->container->make($driverClass);
            }

            return $compiledDrivers;
        });

        $this->container->bind(SlugManager::class, function () {
            return new SlugManager($this->container->make('flarum.http.selectedSlugDrivers'));
        });

        $this->container->singleton('flarum.http.frontend_exceptions', function () {
            return [
                NotAuthenticatedException::class, // 401
                PermissionDeniedException::class, // 403
                ModelNotFoundException::class, // 404
                RouteNotFoundException::class, // 404
            ];
        });

        $this->container->singleton('flarum.http.frontend_handler', function () {
            return new Middleware\HandleErrors(
                $this->container->make(Registry::class),
                $this->container['flarum']->inDebugMode() ? $this->container->make(WhoopsFormatter::class) : new FrontendFormatter($this->container->make('flarum.frontend.forum')),
                $this->container->tagged(Reporter::class),
                $this->container->make('flarum.http.frontend_exceptions')
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setAccessTokenTypes();
    }

    protected function setAccessTokenTypes()
    {
        $models = [
            DeveloperAccessToken::class,
            RememberAccessToken::class,
            SessionAccessToken::class
        ];

        foreach ($models as $model) {
            AccessToken::setModel($model::$type, $model);
        }
    }
}
