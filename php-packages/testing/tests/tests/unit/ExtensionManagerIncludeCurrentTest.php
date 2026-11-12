<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\unit;

use Flarum\Database\Migrator;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\Extension\ExtensionManagerIncludeCurrent;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtensionManagerIncludeCurrentTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/flarum-testing-'.bin2hex(random_bytes(8));
        mkdir($this->dir.'/vendor/composer', 0777, true);

        // The extension under test requires an installed extension whose ID sorts before its own.
        file_put_contents($this->dir.'/composer.json', json_encode([
            'name' => 'acme/plugin',
            'type' => 'flarum-extension',
            'require' => ['flarum/core' => '*', 'acme/base' => '*'],
            'extra' => ['flarum-extension' => ['title' => 'Plugin']],
        ]));

        file_put_contents($this->dir.'/vendor/composer/installed.json', json_encode([
            'packages' => [
                [
                    'name' => 'acme/base',
                    'type' => 'flarum-extension',
                    'install-path' => '../acme/base',
                    'extra' => ['flarum-extension' => ['title' => 'Base']],
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->dir);
        Mockery::close();

        parent::tearDown();
    }

    public static function listedOrders(): array
    {
        return [
            'dependency listed first' => [['acme-base', 'acme-plugin']],
            'dependent listed first' => [['acme-plugin', 'acme-base']],
        ];
    }

    #[Test]
    #[DataProvider('listedOrders')]
    public function current_extension_boots_after_its_required_dependencies(array $enabledIds)
    {
        $manager = $this->manager($enabledIds);

        $this->assertEquals(['acme-base', 'acme-plugin'], array_keys($manager->getEnabledExtensions()));
    }

    #[Test]
    public function current_extension_still_boots_without_its_required_dependencies()
    {
        $manager = $this->manager(['acme-plugin']);

        $this->assertEquals(['acme-plugin'], array_keys($manager->getEnabledExtensions()));
    }

    private function manager(array $enabledIds): ExtensionManagerIncludeCurrent
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturn(null);

        return new ExtensionManagerIncludeCurrent(
            $settings,
            new Paths([
                'base' => $this->dir,
                'public' => $this->dir.'/public',
                'storage' => $this->dir.'/storage',
                'vendor' => $this->dir.'/vendor',
            ]),
            new Container(),
            Mockery::mock(Migrator::class),
            Mockery::mock(Dispatcher::class),
            new Filesystem(),
            Mockery::mock(MaintenanceMode::class),
            $enabledIds
        );
    }
}
