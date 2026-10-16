<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Compatibility;
use Flarum\ExtensionManager\Discover\Packagist;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule these protect: "not for this Flarum" is a claim, and a claim must
 * never be manufactured by a failure.
 *
 * The bug that made this file necessary: `admits()` caught \Throwable, so when
 * composer/semver was missing from the autoloader every Error was swallowed and
 * EVERY package came back "not for this Flarum". Confident, wrong, and with
 * nothing on screen to suggest that nothing had actually been checked. Found by
 * running the endpoint against the real forum, where the verdict for an
 * extension that was installed and working came back "no".
 */
class CompatibilityTest extends TestCase
{
    private function p2(string $name, array $versions): array
    {
        return ['packages' => [$name => $versions]];
    }

    public function test_a_release_that_admits_this_core_is_compatible(): void
    {
        $v = (new Compatibility('2.0.0-rc.8'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => 'v2.0.0-rc.8', 'require' => ['flarum/core' => '^2.0.0-rc.8']],
            ['version' => 'v1.9.0', 'require' => ['flarum/core' => '^1.8']],
        ]));

        $this->assertTrue($v['compatible']);
        $this->assertSame('v2.0.0-rc.8', $v['version']);
    }

    public function test_a_release_candidate_core_is_not_excluded_by_a_caret_two_constraint(): void
    {
        /*
         * 🚨 The case every forum is in right now: Flarum 2 has no stable
         * release, so every install is on an rc. Plain semver says 2.0.0-rc.8 is
         * LESS than 2.0.0 — but Composer's `^2.0` normalises with a -dev lower
         * bound and does admit it, which is why the constraint has to be read
         * with Composer's own parser rather than a hand-rolled comparison.
         */
        $v = (new Compatibility('2.0.0-rc.8'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => '2.1.0', 'require' => ['flarum/core' => '^2.0']],
        ]));

        $this->assertTrue($v['compatible'], 'an rc install must still be offered ^2.0 extensions');
    }

    public function test_a_flarum_one_only_package_says_what_it_wanted(): void
    {
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => '1.4.0', 'require' => ['flarum/core' => '^1.8']],
        ]));

        $this->assertFalse($v['compatible']);
        $this->assertSame('^1.8', $v['requires'], 'a "no" should say what it asked for, not only that it did not fit');
    }

    public function test_a_stable_release_is_preferred_over_a_newer_pre_release(): void
    {
        // Packagist lists newest first, so taking the first match would offer a
        // beta to somebody whose forum cannot install one.
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => '3.0.0-beta.1', 'require' => ['flarum/core' => '^2.0']],
            ['version' => '2.4.0', 'require' => ['flarum/core' => '^2.0']],
        ]));

        $this->assertSame('2.4.0', $v['version']);
        $this->assertSame('stable', $v['stability']);
    }

    public function test_a_pre_release_is_reported_when_it_is_the_only_thing_that_works(): void
    {
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => '2.0.0-beta.7', 'require' => ['flarum/core' => '^2.0']],
            ['version' => '1.2.0', 'require' => ['flarum/core' => '^1.8']],
        ]));

        $this->assertTrue($v['compatible']);
        $this->assertSame('beta', $v['stability'], 'and the screen labels it, rather than presenting it as a release');
    }

    public function test_an_unparseable_constraint_is_a_verdict_and_not_a_crash(): void
    {
        // Composer cannot install it either, so "no" is the honest answer here —
        // unlike a missing class, which is not an answer at all.
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', $this->p2('acme/w', [
            ['version' => '1.0.0', 'require' => ['flarum/core' => 'whatever the author meant']],
        ]));

        $this->assertFalse($v['compatible']);
    }

    public function test_nothing_published_is_unknown_rather_than_incompatible(): void
    {
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', ['packages' => []]);

        $this->assertNull($v['compatible'], '"we could not check" and "it does not work" are different answers');
    }

    public function test_a_verdict_is_cached_against_the_core_version_that_produced_it(): void
    {
        /*
         * 🚨 A verdict is a fact about a package AND the Flarum asking. Keyed on
         * the name alone, an upgrade would keep serving yesterday's answers for
         * a day — exactly when somebody goes looking for extensions that now work.
         */
        $dir = sys_get_temp_dir().'/mw-compat-'.bin2hex(random_bytes(4));
        $calls = 0;

        $body = json_encode($this->p2('acme/w', [['version' => '2.0.0', 'require' => ['flarum/core' => '^2.0']]]));
        $packagist = new Packagist(new Cache($dir), function () use (&$calls, $body) {
            $calls++;

            return $body;
        });

        $packagist->verdicts(['acme/w'], new Compatibility('1.8.0'));
        $packagist->verdicts(['acme/w'], new Compatibility('1.8.0'));
        $this->assertSame(1, $calls, 'the same question twice is one fetch');

        $after = $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));
        $this->assertSame(2, $calls, 'a different core is a different question');
        $this->assertTrue($after['acme/w']['compatible']);

        exec('rm -rf '.escapeshellarg($dir));
    }

    public function test_a_package_that_could_not_be_fetched_is_not_cached_as_unknown(): void
    {
        // Unreachable today is not unreachable tomorrow, and caching "unknown"
        // would hide a working extension for a day.
        $dir = sys_get_temp_dir().'/mw-compat-'.bin2hex(random_bytes(4));
        $calls = 0;

        $packagist = new Packagist(new Cache($dir), function () use (&$calls) {
            $calls++;

            return null;
        });

        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));
        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));

        $this->assertSame(2, $calls);

        exec('rm -rf '.escapeshellarg($dir));
    }
}
