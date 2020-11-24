<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\IdWithSlugDriver;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Flarum\User\UsernameSlugDriver;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->app->bind(Middleware\CheckCsrfToken::class, function ($app) {
            return new Middleware\CheckCsrfToken($app->make('flarum.http.csrfExemptPaths'));
        });

        $this->app->singleton('flarum.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithSlugDriver::class
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class
                ],
            ];
        });

        $this->app->singleton('flarum.http.selectedSlugDrivers', function () {
            $settings = $this->app->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($this->app->make('flarum.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverClass = $resourceDrivers[$settings->get("slug_driver_$resourceClass", 'default')];
                echo "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA";
                echo "slug_driver_$resourceClass";
                //echo $settings->get("slug_driver_$resourceClass", 'default');
                $compiledDrivers[$resourceClass] = $this->app->make($driverClass);
            }

            return $compiledDrivers;
        });

        $this->app->singleton('flarum.http.resourceUrlGenerators', function () {
            $slugManager = $this->app->make(SlugManager::class);

            return [
                Discussion::class => function (UrlGenerator $urlGenerator, Discussion $discussion) use ($slugManager) {
                    return $urlGenerator->to('forum')->route('discussion', [
                        'id' => $slugManager->forResource(Discussion::class)->toSlug($discussion)
                    ]);
                },
                Post::class => function (UrlGenerator $urlGenerator, Post $post) use ($slugManager) {
                    return $urlGenerator->to('forum')->route('user', [
                        'id' => $slugManager->forResource(Discussion::class)->toSlug($post->discussion),
                        'near' => $post->id,
                    ]);
                },
                User::class => function (UrlGenerator $urlGenerator, User $user) use ($slugManager) {
                    return $urlGenerator->to('forum')->route('user', [
                        'id' => $slugManager->forResource(User::class)->toSlug($user)
                    ]);
                },
            ];
        });
        $this->app->bind(SlugManager::class, function () {
            return new SlugManager($this->app->make('flarum.http.selectedSlugDrivers'));
        });

        $this->app->bind(UrlGenerator::class, function () {
            return new UrlGenerator(
                $this->app->make(Application::class),
                $this->app->make('flarum.http.resourceUrlGenerators')
            );
        });
    }
}
