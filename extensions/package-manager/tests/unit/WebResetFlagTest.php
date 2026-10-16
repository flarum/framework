<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Host\Opcache;
use PHPUnit\Framework\TestCase;

/** A CLI update asks the web server to drop its compiled code; one web request takes it up. */
class WebResetFlagTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-reset-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_a_request_is_pending_until_a_web_request_takes_it(): void
    {
        $this->assertFalse(Opcache::webResetPending($this->dir));

        Opcache::requestWebReset($this->dir);
        $this->assertTrue(Opcache::webResetPending($this->dir), 'the storage directory is made if missing');

        Opcache::honourWebReset($this->dir);
        $this->assertFalse(Opcache::webResetPending($this->dir));
        $this->assertSame([], glob($this->dir.'/extension-manager/opcache-reset*'), 'nothing is left behind');
    }

    public function test_no_flag_is_a_single_stat_and_nothing_else(): void
    {
        Opcache::honourWebReset($this->dir);

        $this->assertDirectoryDoesNotExist($this->dir.'/extension-manager');
    }

    /**
     * 🚨 Taken before any extension boots, or a stale extend.php that registers
     * a deleted class kills every request before the reset could fix it.
     */
    public function test_the_flag_is_taken_from_composer_autoload_not_only_the_provider(): void
    {
        $root = dirname(__DIR__, 2);
        $files = json_decode((string) file_get_contents($root.'/composer.json'), true)['autoload']['files'] ?? [];

        $this->assertContains('bootstrap/web-reset.php', $files);
        $bootstrap = (string) file_get_contents($root.'/bootstrap/web-reset.php');
        $this->assertStringContainsString('StorageDir::of(', $bootstrap);
        $this->assertStringContainsString("'/opcache-reset'", $bootstrap);
    }
}
