<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\SessionDriverInterface;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\NullSessionHandler;
use InvalidArgumentException;
use SessionHandlerInterface;

class SessionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @test
     */
    public function default_driver_exists_by_default()
    {
        $this->expectNotToPerformAssertions();
        $this->app()->getContainer()->make('session.handler');
    }

    /**
     * @test
     */
    public function custom_driver_doesnt_exist_by_default()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->app()->getContainer()->make('session')->driver('flarum-acme');
    }

    /**
     * @test
     */
    public function custom_driver_exists_if_added()
    {
        $this->extend((new Extend\Session())->driver('flarum-acme', AcmeSessionDriver::class));

        $driver = $this->app()->getContainer()->make('session')->driver('flarum-acme');

        $this->assertEquals(NullSessionHandler::class, get_class($driver->getHandler()));
    }

    /**
     * @test
     */
    public function custom_driver_overrides_laravel_defined_drivers_if_added()
    {
        $this->extend((new Extend\Session())->driver('redis', AcmeSessionDriver::class));

        $driver = $this->app()->getContainer()->make('session')->driver('redis');

        $this->assertEquals(NullSessionHandler::class, get_class($driver->getHandler()));
    }

    /**
     * @test
     */
    public function uses_default_driver_if_driver_from_config_file_not_configured()
    {
        $this->config('session.driver', null);

        $handler = $this->app()->getContainer()->make('session.handler');

        $this->assertEquals(FileSessionHandler::class, get_class($handler));
    }

    /**
     * @test
     */
    public function uses_default_driver_if_configured_driver_from_config_file_unavailable()
    {
        $this->config('session.driver', 'nevergonnagiveyouup');

        $handler = $this->app()->getContainer()->make('session.handler');

        $this->assertEquals(FileSessionHandler::class, get_class($handler));
    }

    /**
     * @test
     */
    public function uses_custom_driver_from_config_file_if_configured_and_available()
    {
        $this->extend(
            (new Extend\Session)->driver('flarum-acme', AcmeSessionDriver::class)
        );

        $this->config('session.driver', 'flarum-acme');

        $handler = $this->app()->getContainer()->make('session.handler');

        $this->assertEquals(NullSessionHandler::class, get_class($handler));
    }
}

class AcmeSessionDriver implements SessionDriverInterface
{
    public function build(SettingsRepositoryInterface $settings, Config $config): SessionHandlerInterface
    {
        return new NullSessionHandler();
    }
}
