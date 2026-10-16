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

/**
 * 🚨 The rule these protect: an update that cannot be seen has not happened.
 *
 * Everything else in this extension is about whether an update can RUN. This is
 * about whether anybody can tell that it did. On a host tuned with
 * `opcache.validate_timestamps=0`, new files land, every phase reports success,
 * and the site serves the old compiled code until PHP is restarted — silent,
 * total, and indistinguishable from the update never having run.
 *
 * The same fact is why this extension replaces directories in place rather than
 * flipping a symlink between prepared slots: `opcache.revalidate_path` is false
 * by default, so opcache resolves a symlink once and caches it. Slots would
 * trade a microsecond of a missing package for minutes of serving stale code.
 */
class OpcacheTest extends TestCase
{
    public function test_a_revalidate_window_is_treated_as_stale_risk_not_as_fine(): void
    {
        /*
         * 🚨 The bug this test exists for, found by installing this extension on
         * a production forum. It has validate_timestamps ON — which looks fine —
         * and revalidate_freq 60, the default in Flarum's own Docker image. PHP
         * therefore does not re-read a changed file for up to a MINUTE, and
         * every page returned 500 with "class not found" for a class that was
         * sitting right there on disk.
         *
         * Reading only validate_timestamps called that "fine" and did nothing.
         */
        $s = (new Opcache())->situationFrom(['opcache.enable' => true, 'opcache.validate_timestamps' => true, 'opcache.revalidate_freq' => 60]);

        $this->assertSame(Opcache::STALE_RISK, $s['state']);
        $this->assertTrue($s['validates']);
        $this->assertSame(60, $s['freq']);
    }

    public function test_no_opcache_at_all_is_the_only_state_needing_nothing(): void
    {
        $s = (new Opcache())->situationFrom(['opcache.enable' => false]);

        $this->assertSame(Opcache::FINE, $s['state']);
    }

    public function test_a_worker_that_cannot_clear_says_what_will_happen_anyway(): void
    {
        // Where timestamps ARE checked, a worker that cannot reset is not a
        // problem to escalate — the change becomes live on its own shortly, and
        // saying so is more useful than a warning about restarting PHP-FPM.
        $o = new class extends Opcache {
            public function situation(): array
            {
                return ['state' => Opcache::STALE_RISK, 'enabled' => true, 'validates' => true, 'freq' => 60, 'canReset' => true];
            }
        };

        $result = $o->clear();

        $this->assertFalse($result['done']);
        $this->assertStringContainsString('60 second', $result['why']);
        $this->assertStringNotContainsString('restart PHP-FPM', $result['why']);
    }

    public function test_a_worker_says_plainly_that_it_cannot_fix_this(): void
    {
        /*
         * 🚨 The queue-worker case, and the reason it is called out rather than
         * quietly returning success: opcache_reset() in a CLI worker resets that
         * worker's own cache, which nobody is using, and leaves the web pool
         * exactly as stale as before. Reporting success for having called a
         * function is how this gets diagnosed three days later as "the update
         * didn't work".
         */
        $o = new class extends Opcache {
            public function situation(): array
            {
                return ['state' => Opcache::STALE_RISK, 'enabled' => true, 'validates' => false, 'freq' => 0, 'canReset' => true];
            }
        };

        $result = $o->clear();

        // This suite runs under the CLI SAPI, which is the situation being tested.
        $this->assertFalse($result['done']);
        $this->assertStringContainsString('restart PHP-FPM', $result['why']);
        $this->assertStringContainsString('files are updated', $result['why'], 'and it says what IS true');
    }

    public function test_the_real_situation_is_answered_without_throwing(): void
    {
        // Whatever this machine is configured to do, the shape must hold — this
        // is consulted on a page load and on every finalise.
        $s = (new Opcache())->situation();

        $this->assertContains($s['state'], [Opcache::FINE, Opcache::STALE_RISK]);
        $this->assertIsBool($s['enabled']);
        $this->assertIsBool($s['validates']);
        $this->assertIsInt($s['freq']);
    }

    public function test_clearing_never_throws(): void
    {
        // 🚨 It runs as the last step of a finished update. By then the files,
        // the lock and Composer's record all agree; a cache that could not be
        // cleared is something to say, never a reason to fail work that was
        // correct and offer to undo it.
        $result = (new Opcache())->clear();

        $this->assertIsBool($result['done']);
        $this->assertNotSame('', $result['why']);
    }
}
