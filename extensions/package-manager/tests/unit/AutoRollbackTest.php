<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Host\ErrorLog;
use Flarum\ExtensionManager\Host\Verdict;
use PHPUnit\Framework\TestCase;

/**
 * The rule that decides whether to undo somebody's update, and the sentence
 * that tells them why.
 */
class AutoRollbackTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-health-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/logs', 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/logs/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir.'/logs');
        @rmdir($this->dir);
    }

    public function test_a_site_that_worked_and_now_does_not_is_this_update_s_fault(): void
    {
        $this->assertSame(Verdict::BROKEN_BY_THIS, Verdict::from('ok', false));
    }

    /**
     * 🚨 The case that makes an automatic rollback safe to have at all. A forum
     * that was already down — bad config, full disk, database gone — fails the
     * check afterwards too, and undoing a good update for it would be the
     * feature causing the outage it exists to prevent.
     */
    public function test_a_site_that_was_already_down_does_not_get_the_update_blamed(): void
    {
        $this->assertSame(Verdict::ALREADY_BROKEN, Verdict::from('down', false));
    }

    /**
     * 🚨 And the case that makes it safe on hosts it cannot run on. This branch
     * is reached exactly where the checker does not work, so treating "no
     * answer" as a failure would undo every update ever made on that host.
     */
    public function test_a_host_that_cannot_be_checked_never_blames_the_update(): void
    {
        $this->assertSame(Verdict::NOT_JUDGED, Verdict::from('unchecked', false));
        $this->assertSame(Verdict::NOT_JUDGED, Verdict::from('unchecked', true));
    }

    public function test_a_site_that_answers_is_healthy_however_it_started(): void
    {
        $this->assertSame(Verdict::HEALTHY, Verdict::from('ok', true));
        $this->assertSame(Verdict::HEALTHY, Verdict::from('down', true));
    }

    /**
     * "The update broke the site" is not actionable. The name of the file and
     * the line is what tells an admin whose bug this is.
     */
    public function test_the_reason_comes_from_what_the_site_actually_said(): void
    {
        $now = time();
        file_put_contents($this->logFile(), implode("\n", [
            '['.gmdate('Y-m-d\TH:i:s.uP', $now - 5).'] flarum.INFO: something ordinary',
            '['.gmdate('Y-m-d\TH:i:s.uP', $now - 2).'] flarum.ERROR: TypeError: Flarum\Extend\Event::listen(): Argument #2 must be of type callable|string, array given in /var/www/html/vendor/ramon/classifieds/extend.php:87',
            'Stack trace:',
            '#0 /var/www/html/vendor/flarum/core/src/Extension/Extension.php(347)',
        ])."\n");

        $why = (new ErrorLog($this->dir))->latest($now - 60);

        $this->assertNotNull($why);
        $this->assertStringContainsString('Extend\Event::listen', $why);
        $this->assertStringNotContainsString('Stack trace', $why);
    }

    /**
     * 🚨 A failure from last Tuesday must never be offered as the explanation
     * for today's, or the rollback message names an innocent extension.
     */
    public function test_an_error_from_before_the_update_is_not_offered_as_the_reason(): void
    {
        $now = time();
        file_put_contents($this->logFile(), '['.gmdate('Y-m-d\TH:i:s.uP', $now - 7200)
            ."] flarum.ERROR: something that broke two hours ago\n");

        $this->assertNull((new ErrorLog($this->dir))->latest($now - 60));
    }

    public function test_no_log_at_all_is_not_an_error(): void
    {
        $this->assertNull((new ErrorLog($this->dir))->latest(time() - 60));
    }

    private function logFile(): string
    {
        return $this->dir.'/logs/flarum-'.gmdate('Y-m-d').'.log';
    }
}
