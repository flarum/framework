<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Tests\integration\TestCase;

class ServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function providers_dont_work_by_default()
    {
        $this->app();

        $this->assertIsArray(
            $this->app->getContainer()->make('flarum.forum.middleware')
        );
    }

    /**
     * @test
     */
    public function providers_order_is_correct()
    {
        $this->extend(
            (new Extend\ServiceProvider())
                ->register(CustomServiceProvider::class)
                ->register(SecondCustomServiceProvider::class)
        );

        $this->app();

        $this->assertEquals(
            'overriden_by_provider_test__final',
            $this->app->getContainer()->make('flarum.forum.middleware')
        );
    }
}

class CustomServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // First we override the singleton here.
        $this->app->extend('flarum.forum.middleware', function () {
            return 'overriden_by_provider_test';
        });
    }

    public function boot()
    {
        // Third we override one last time here, to make sure this is the final result.
        $this->app->extend('flarum.forum.middleware', function ($forumRoutes) {
            return 'overriden_by_provider_test__final';
        });
    }
}

class SecondCustomServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Second we check that the singleton was overriden here.
        $this->app->extend('flarum.forum.middleware', function ($forumRoutes) {
            if ($forumRoutes !== 'overriden_by_provider_test') {
                throw new ProviderTestException('CustomServiceProvider did not override the singleton.');
            }

            return $forumRoutes;
        });
    }
}

class ProviderTestException extends \Exception
{
    // ...
}
