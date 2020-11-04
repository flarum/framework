<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Tests\integration\TestCase;
use Flarum\Extend;
use Illuminate\Contracts\Container\BindingResolutionException;

class ServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function provider_doesnt_work_by_default()
    {
        $this->app();

        $this->expectException(BindingResolutionException::class);

        $this->app->getContainer()->make('flarum.tests.service_provider_test');
    }

    /**
     * @test
     */
    public function provider_works_if_added()
    {
        $this->extend((new Extend\ServiceProvider())->register(CustomServiceProvider::class));

        $this->app();

        $this->assertTrue(
            $this->app->getContainer()->make('flarum.tests.service_provider_test')
        );
    }
}

class CustomServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('flarum.tests.service_provider_test', function () {
            return true;
        });
    }
}
