<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\Application;
use Flarum\Mail\DriverInterface;
use Flarum\Mail\NullDriver;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\SettingsServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class ValidateMailConfiguration
{
    /**
     * @var SettingsServiceProvider
     */
    protected $settings;

    /**
     * @var Application
     */
    protected $container;

    /**
     * @param Container $container
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(Container $container, SettingsRepositoryInterface $settings)
    {
        $this->container = $container;
        $this->settings = $settings;
    }

    public function whenSettingsSaving(Saving $event)
    {
        if (! isset($event->settings['mail_driver'])) {
            return;
        }

        $driver = $this->getDriver($event->settings);

        $this->getValidator($driver, $event->settings)->validate();
    }

    public function getWorkingDriver()
    {
        $settings = $this->settings->all();
        $driver = $this->getDriver($settings);
        $validator = $this->getValidator($driver, $settings);

        return$validator->passes()
            ? $driver
            : $this->container->make(NullDriver::class);
    }

    /**
     * @param DriverInterface $driver
     * @param array $settings
     * @return Validator
     */
    protected function getValidator($driver, $settings)
    {
        $rules = $driver->availableSettings();
        $settings = Arr::only($settings, array_keys($rules));

        return $this->container->make('validator')->make($settings, $rules);
    }

    protected function getDriver($settings)
    {
        $drivers = $this->container->make('mail.supported_drivers');
        $specifiedDriver = Arr::get($settings, 'mail_driver');
        $driverClass = Arr::get($drivers, $specifiedDriver);

        return $specifiedDriver && $driverClass
            ? $this->container->make($driverClass)
            : $this->container->make(NullDriver::class);
    }
}
