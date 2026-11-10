<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Foundation\Console\InfoCommand;
use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\Test;

class InfoCommandMemoryLimitTest extends ConsoleTestCase
{
    private string $confDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->confDir = sys_get_temp_dir().'/flarum-info-confd-'.uniqid();
        mkdir($this->confDir, 0777, true);
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->confDir.'/*'));
        @rmdir($this->confDir);

        parent::tearDown();
    }

    /**
     * A command that detects its memory limit from the given fixture
     * directories rather than the real system paths.
     */
    private function commandReading(array $files, array $confDirs): InfoCommand
    {
        $container = $this->app()->getContainer();

        return new class($container, $files, $confDirs) extends InfoCommand {
            public function __construct($container, private array $files, private array $confDirs)
            {
                parent::__construct(
                    $container->make(\Flarum\Extension\ExtensionManager::class),
                    $container->make(\Flarum\Foundation\Config::class),
                    $container->make(\Flarum\Settings\SettingsRepositoryInterface::class),
                    $container->make(\Illuminate\Database\ConnectionInterface::class),
                    $container->make(\Flarum\Foundation\ApplicationInfoProvider::class),
                );
            }

            protected function webServerMemoryLimitFiles(): array
            {
                return $this->files;
            }

            protected function webServerMemoryLimitConfDirs(): array
            {
                return $this->confDirs;
            }

            public function detect(): ?string
            {
                return $this->detectWebServerMemoryLimit();
            }
        };
    }

    #[Test]
    public function it_reads_a_memory_limit_from_a_confd_override()
    {
        // The case this fixes: distros keep the base php.ini limit low and set
        // the real one in a conf.d file, which the fixed-path list never covers.
        file_put_contents($this->confDir.'/99-overrides.ini', "memory_limit = 512M\n");

        $command = $this->commandReading([], [$this->confDir]);

        $this->assertEquals('512M', $command->detect());
    }

    #[Test]
    public function a_later_confd_file_overrides_an_earlier_one()
    {
        // conf.d files load in name order, so a later file's value wins.
        file_put_contents($this->confDir.'/10-base.ini', "memory_limit = 256M\n");
        file_put_contents($this->confDir.'/99-overrides.ini', "memory_limit = 512M\n");

        $command = $this->commandReading([], [$this->confDir]);

        $this->assertEquals('512M', $command->detect());
    }

    #[Test]
    public function a_direct_file_is_preferred_over_confd()
    {
        // A memory_limit found in the file list is returned before the conf.d
        // scan runs at all.
        $file = $this->confDir.'/php.ini';
        file_put_contents($file, "memory_limit = 1G\n");
        file_put_contents($this->confDir.'/99-overrides.ini', "memory_limit = 512M\n");

        $command = $this->commandReading([$file], [$this->confDir]);

        $this->assertEquals('1G', $command->detect());
    }

    #[Test]
    public function it_returns_null_when_nothing_declares_a_limit()
    {
        $command = $this->commandReading([], [$this->confDir]);

        $this->assertNull($command->detect());
    }

    #[Test]
    public function the_default_confd_list_covers_the_debian_ubuntu_fpm_path()
    {
        // The bug this fixes: Debian and Ubuntu put memory_limit overrides in
        // /etc/php/{version}/fpm/conf.d/, which the detection never scanned, so
        // it reported the base php.ini value. Pin that this path is covered.
        $phpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $container = $this->app()->getContainer();
        $command = new class($container) extends InfoCommand {
            public function __construct($container)
            {
                parent::__construct(
                    $container->make(\Flarum\Extension\ExtensionManager::class),
                    $container->make(\Flarum\Foundation\Config::class),
                    $container->make(\Flarum\Settings\SettingsRepositoryInterface::class),
                    $container->make(\Illuminate\Database\ConnectionInterface::class),
                    $container->make(\Flarum\Foundation\ApplicationInfoProvider::class),
                );
            }

            public function confDirs(): array
            {
                return $this->webServerMemoryLimitConfDirs();
            }
        };

        $this->assertContains("/etc/php/{$phpVersion}/fpm/conf.d", $command->confDirs());
    }
}
