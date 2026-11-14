<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation\Console;

use Flarum\Foundation\Console\InfoCommand;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InfoCommandWebPhpVersionTest extends TestCase
{
    private string $binary;

    public function setUp(): void
    {
        parent::setUp();

        $this->binary = sys_get_temp_dir().'/flarum-info-php-fpm-'.uniqid();
    }

    public function tearDown(): void
    {
        @unlink($this->binary);

        parent::tearDown();
    }

    /**
     * A command that detects the web PHP version from the given binaries
     * rather than the real system paths.
     */
    private function commandReading(?array $binaries = null): InfoCommand
    {
        return new class($binaries) extends InfoCommand {
            public function __construct(private ?array $binaries)
            {
            }

            protected function webServerPhpBinaries(): array
            {
                return $this->binaries ?? parent::webServerPhpBinaries();
            }

            public function binaries(): array
            {
                return $this->webServerPhpBinaries();
            }

            public function detect(): ?string
            {
                return $this->detectWebServerPhpVersion();
            }
        };
    }

    #[Test]
    public function the_default_binary_list_has_no_cli_binaries()
    {
        // The bug this fixes: /usr/bin/php is often a different CLI build than
        // the site's FPM, and was reported as the web version.
        $binaries = $this->commandReading()->binaries();

        $this->assertNotContains('/usr/bin/php', $binaries);
        $this->assertNotContains('/usr/local/bin/php', $binaries);
        $this->assertContains('/usr/local/sbin/php-fpm', $binaries);
    }

    #[Test]
    public function it_reads_the_version_from_an_fpm_binary()
    {
        if (! function_exists('exec')) {
            $this->markTestSkipped('exec() is disabled.');
        }

        file_put_contents($this->binary, "#!/bin/sh\necho 'PHP 8.3.31 (fpm-fcgi) (built: Jan  1 2026 00:00:00)'\n");
        chmod($this->binary, 0755);

        $this->assertEquals('8.3.31', $this->commandReading([$this->binary])->detect());
    }

    #[Test]
    public function it_returns_null_when_no_binary_exists()
    {
        $this->assertNull($this->commandReading([$this->binary])->detect());
    }
}
