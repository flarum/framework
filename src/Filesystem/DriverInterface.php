<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filesystem;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;

interface DriverInterface
{
    /**
     * Construct a Laravel Cloud filesystem for this filesystem driver.
     * Settings and configuration can either be pulled from the Flarum settings repository
     * or the config.php file.
     *
     * Typically, this is done by wrapping a Flysystem adapter in Laravel's
     * `Illuminate\Filesystem\FilesystemAdapter` class.
     * You should ensure that the Flysystem adapter you use has a `getUrl` method.
     * If it doesn't, you should create a subclass implementing that method.
     * Otherwise, this driver won't work for public-facing disks
     * like `flarum-assets` or `flarum-avatars`.
     *
     * @param string $diskName: The name of a disk this driver is being used for.
     *                   This is generally used to locate disk-specific settings.
     * @param SettingsRepositoryInterface $settings: An instance of the Flarum settings repository.
     * @param Config $config: An instance of the wrapper class around `config.php`.
     * @param array $localConfig: The configuration array that would have been used
     *                            if this disk were using the 'local' filesystem driver.
     *                            Some of these settings might be useful (e.g. visibility, )
     */
    public function build(string $diskName, SettingsRepositoryInterface $settings, Config $config, array $localConfig): Cloud;
}
