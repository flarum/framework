<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Filesystem\DriverInterface;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;
use InvalidArgumentException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;

class FilesystemTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function custom_disk_doesnt_exist_by_default()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->app()->getContainer()->make('filesystem')->disk('flarum-uploads');
    }

    #[Test]
    public function custom_disk_exists_if_added_and_uses_local_adapter_by_default()
    {
        $this->extend((new Extend\Filesystem)->disk('flarum-uploads', function (Paths $paths, UrlGenerator $url) {
            return [
                'root' => "$paths->public/assets/uploads",
                'url' => $url->to('forum')->path('assets/uploads')
            ];
        }));

        /** @var FilesystemAdapter $uploadsDisk */
        $uploadsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-uploads');

        $this->assertEquals($uploadsDisk->getAdapter()::class, LocalFilesystemAdapter::class);
    }

    #[Test]
    public function custom_disk_exists_if_added_via_invokable_class_and_uses_local_adapter_by_default()
    {
        $this->extend((new Extend\Filesystem)->disk('flarum-uploads', UploadsDisk::class));

        /** @var FilesystemAdapter $uploadsDisk */
        $uploadsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-uploads');

        $this->assertEquals($uploadsDisk->getAdapter()::class, LocalFilesystemAdapter::class);
    }

    #[Test]
    public function disk_uses_local_adapter_if_configured_adapter_unavailable()
    {
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('disk_driver.flarum-assets', 'nonexistent_driver');

        /** @var FilesystemAdapter $assetsDisk */
        $assetsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $this->assertEquals($assetsDisk->getAdapter()::class, LocalFilesystemAdapter::class);
    }

    #[Test]
    public function disk_uses_local_adapter_if_configured_adapter_from_config_file_unavailable()
    {
        $this->config('disk_driver.flarum-assets', 'null');

        /** @var FilesystemAdapter $assetsDisk */
        $assetsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $this->assertEquals($assetsDisk->getAdapter()::class, LocalFilesystemAdapter::class);
    }

    #[Test]
    public function disk_uses_custom_adapter_if_configured_and_available()
    {
        $this->extend(
            (new Extend\Filesystem)->driver('null', NullFilesystemDriver::class)
        );

        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('disk_driver.flarum-assets', 'null');

        /** @var FilesystemAdapter $assetsDisk */
        $assetsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $this->assertEquals($assetsDisk->getAdapter()::class, InMemoryFilesystemAdapter::class);
    }

    #[Test]
    public function disk_uses_custom_adapter_from_config_file_if_configured_and_available()
    {
        $this->extend(
            (new Extend\Filesystem)->driver('null', NullFilesystemDriver::class)
        );

        $this->config('disk_driver.flarum-assets', 'null');

        /** @var FilesystemAdapter $assetsDisk */
        $assetsDisk = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $this->assertEquals($assetsDisk->getAdapter()::class, InMemoryFilesystemAdapter::class);
    }
}

class NullFilesystemDriver implements DriverInterface
{
    public function build(string $diskName, SettingsRepositoryInterface $settings, Config $config, array $localConfig): Cloud
    {
        // The internal adapter
        $adapter = new InMemoryFilesystemAdapter();

        // The FilesystemOperator
        $filesystem = new \League\Flysystem\Filesystem($adapter);

        return new FilesystemAdapter($filesystem, $adapter);
    }
}

class UploadsDisk
{
    public function __invoke(Paths $paths, UrlGenerator $url)
    {
        return [
            'root' => "$paths->public/assets/uploads",
            'url' => $url->to('forum')->path('assets/uploads')
        ];
    }
}
