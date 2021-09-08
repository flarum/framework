<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filesystem;

use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager as LaravelFilesystemManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class FilesystemManager extends LaravelFilesystemManager
{
    protected $diskLocalConfig = [];
    protected $drivers = [];

    public function __construct(Container $app, array $diskLocalConfig, array $drivers)
    {
        parent::__construct($app);

        $this->diskLocalConfig = $diskLocalConfig;
        $this->drivers = $drivers;
    }

    /**
     * @inheritDoc
     */
    protected function resolve($name, $config = null): Filesystem
    {
        $localConfig = $config ?? $this->getLocalConfig($name);

        if (empty($localConfig)) {
            throw new InvalidArgumentException("Disk [{$name}] has not been declared. Use the Filesystem extender to do this.");
        }

        $driver = $config['driver'] ?? $this->getDriver($name);

        if ($driver === 'local') {
            return $this->createLocalDriver($localConfig);
        }

        $settings = $this->app->make(SettingsRepositoryInterface::class);
        $config = $this->app->make(Config::class);

        return $driver->build($name, $settings, $config, $localConfig);
    }

    /**
     * @return string|DriverInterface
     */
    protected function getDriver(string $name)
    {
        $config = $this->app->make(Config::class);
        $settings = $this->app->make(SettingsRepositoryInterface::class);

        $key = "disk_driver.$name";
        $configuredDriver = Arr::get($config, $key, $settings->get($key, 'local'));

        return Arr::get($this->drivers, $configuredDriver, 'local');
    }

    protected function getLocalConfig(string $name): array
    {
        if (! array_key_exists($name, $this->diskLocalConfig)) {
            return [];
        }

        $paths = $this->app->make(Paths::class);
        $url = $this->app->make(UrlGenerator::class);

        return $this->diskLocalConfig[$name]($paths, $url);
    }
}
