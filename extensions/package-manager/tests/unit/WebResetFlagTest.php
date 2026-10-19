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
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        @chmod($this->dir, 0775);
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_a_request_is_pending_until_a_web_request_takes_it(): void
    {
        $this->assertFalse(Opcache::webResetPending($this->dir));

        Opcache::requestWebReset($this->dir);
        $this->assertTrue(Opcache::webResetPending($this->dir));

        Opcache::honourWebReset($this->dir);
        $this->assertFalse(Opcache::webResetPending($this->dir));
        $this->assertSame([], glob($this->dir.'/*opcache-reset*'), 'nothing is left behind');
    }

    public function test_no_flag_is_a_single_stat_and_nothing_else(): void
    {
        Opcache::honourWebReset($this->dir);

        $this->assertSame([], glob($this->dir.'/*'));
    }

    /**
     * A flag the web server cannot take (made by root, in a directory it
     * cannot write to) is honoured once, then left alone: a cache reset since
     * the flag was written means it has been.
     */
    public function test_a_flag_that_cannot_be_taken_is_not_honoured_on_every_request(): void
    {
        Opcache::requestWebReset($this->dir);
        $written = (int) filemtime($this->dir.'/extension-manager-opcache-reset');
        chmod($this->dir, 0555);

        if (is_writable($this->dir)) {
            $this->markTestSkipped('running as a user who can write anywhere');
        }

        Opcache::honourWebReset($this->dir, ['opcache_statistics' => ['last_restart_time' => $written + 5]]);
        $this->assertTrue(Opcache::webResetPending($this->dir), 'it stays, and is compared rather than retried');

        // Asked again later: honoured again, because it is newer than the last reset.
        chmod($this->dir, 0775);
        touch($this->dir.'/extension-manager-opcache-reset', $written + 10);
        chmod($this->dir, 0555);
        Opcache::honourWebReset($this->dir, ['opcache_statistics' => ['last_restart_time' => $written + 5]]);
        $this->assertTrue(Opcache::webResetPending($this->dir));
    }

    /**
     * Taken before any extension boots, or a stale extend.php that registers
     * a deleted class kills every request before the reset could fix it. And
     * it costs one stat: no class is loaded unless the flag is there.
     */
    public function test_the_flag_is_taken_from_composer_autoload_not_only_the_provider(): void
    {
        $root = dirname(__DIR__, 2);
        $files = json_decode((string) file_get_contents($root.'/composer.json'), true)['autoload']['files'] ?? [];

        $this->assertContains('bootstrap/web-reset.php', $files);
        $bootstrap = (string) file_get_contents($root.'/bootstrap/web-reset.php');
        $this->assertStringContainsString("is_file(FLARUM_EXTENSION_MANAGER_EARLY_STORAGE.'/extension-manager-opcache-reset')", $bootstrap);
    }
}
