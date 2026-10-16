<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Plan\CoreUpgrade;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule these protect: the question "what would upgrading Flarum do to my
 * extensions" is answered BEFORE anything is pressed, per extension, by name.
 *
 * Composer's answer to the same question is a refusal at the end of a long wait,
 * written in terms of packages and version constraints, from which somebody then
 * has to work out which of their thirty extensions is the problem.
 */
class CoreUpgradeTest extends TestCase
{
    private function ext(string $name, string $version, ?string $requires): array
    {
        $row = ['name' => $name, 'version' => $version, 'type' => 'flarum-extension'];

        if ($requires !== null) {
            $row['require'] = ['flarum/core' => $requires];
        }

        return $row;
    }

    public function test_the_lock_alone_answers_for_extensions_that_already_fit(): void
    {
        /*
         * 🚨 The important property is not the verdict, it is that NOTHING was
         * asked. Doing one Packagist call per extension took 28 seconds against
         * a real forum's 58 extensions — an endpoint that cannot finish inside
         * thirty seconds, built into the screen whose whole promise is that
         * nothing takes longer than one request.
         */
        $r = (new CoreUpgrade())->preflight([
            $this->ext('acme/a', '2.0.0', '^2.0'),
            $this->ext('acme/b', '1.4.0', '^1.8 || ^2.0'),
        ], '2.1.0');

        $this->assertSame([], $r['pending'], 'nothing that fits should cost a network call');
        $this->assertSame(0, $r['blocked']);
        $this->assertSame(['ready', 'ready'], array_column($r['verdicts'], 'state'));
    }

    public function test_extensions_that_do_not_fit_are_handed_back_to_be_asked_about(): void
    {
        $r = (new CoreUpgrade())->preflight([
            $this->ext('acme/old', '1.0.0', '^1.8'),
            $this->ext('acme/fine', '2.0.0', '^2.0'),
        ], '2.1.0');

        $this->assertSame(['acme/old'], $r['pending']);
        $this->assertSame('unknown', $r['verdicts'][0]['state'], 'unresolved is unknown, never assumed');
    }

    public function test_a_newer_release_that_fits_means_the_extension_comes_along(): void
    {
        $core = new CoreUpgrade();
        $r = $core->preflight([$this->ext('acme/old', '1.0.0', '^1.8')], '2.1.0');

        $merged = $core->merge($r['verdicts'], [
            'acme/old' => ['compatible' => true, 'version' => '2.3.0', 'stability' => 'stable'],
        ]);

        $this->assertSame('needs-update', $merged['verdicts'][0]['state']);
        $this->assertSame('2.3.0', $merged['verdicts'][0]['to']);
        $this->assertSame(1, $merged['updating']);
        $this->assertSame(0, $merged['blocked']);
    }

    public function test_nothing_published_that_fits_is_what_stops_the_upgrade(): void
    {
        $core = new CoreUpgrade();
        $r = $core->preflight([$this->ext('acme/old', '1.0.0', '^1.8')], '2.1.0');

        $merged = $core->merge($r['verdicts'], [
            'acme/old' => ['compatible' => false, 'requires' => '^1.8'],
        ]);

        $this->assertSame('blocked', $merged['verdicts'][0]['state']);
        $this->assertSame('^1.8', $merged['verdicts'][0]['requires'], 'and it says what it wanted');
        $this->assertSame(1, $merged['blocked']);
    }

    public function test_a_package_nobody_could_check_stays_unknown_and_is_not_called_blocked(): void
    {
        /*
         * 🚨 "Not on Packagist" is not "incompatible". Promoting it would tell
         * somebody their upgrade is impossible when it may be fine — worse than
         * saying nothing, because they would believe it and stop.
         */
        $core = new CoreUpgrade();
        $r = $core->preflight([$this->ext('acme/private', '1.0.0', '^1.8')], '2.1.0');

        $merged = $core->merge($r['verdicts'], [
            'acme/private' => ['compatible' => null, 'version' => null, 'requires' => null],
        ]);

        $this->assertSame('unknown', $merged['verdicts'][0]['state']);
        $this->assertSame(0, $merged['blocked']);
    }

    public function test_what_stops_you_is_listed_first(): void
    {
        // The list exists to answer "what stops me". Two blockers buried among
        // thirty ready rows in alphabetical order is the same as no answer.
        $core = new CoreUpgrade();
        $r = $core->preflight([
            $this->ext('acme/aaa', '2.0.0', '^2.0'),
            $this->ext('acme/zzz', '1.0.0', '^1.8'),
            $this->ext('acme/mmm', '1.0.0', '^1.8'),
        ], '2.1.0');

        $merged = $core->merge($r['verdicts'], [
            'acme/zzz' => ['compatible' => false, 'requires' => '^1.8'],
            'acme/mmm' => ['compatible' => true, 'version' => '2.0.0'],
        ]);

        $this->assertSame(
            ['acme/zzz', 'acme/mmm', 'acme/aaa'],
            array_column($merged['verdicts'], 'package'),
            'blocked, then coming along, then already fine'
        );
    }

    public function test_things_that_are_not_extensions_are_not_in_the_list(): void
    {
        // A forum's lock holds two hundred libraries. They are not the question.
        $r = (new CoreUpgrade())->preflight([
            ['name' => 'psr/log', 'version' => '3.0.0', 'type' => 'library'],
            ['name' => 'flarum/core', 'version' => '2.0.0', 'type' => 'flarum-core'],
            $this->ext('acme/a', '2.0.0', '^2.0'),
        ], '2.1.0');

        $this->assertSame(['acme/a'], array_column($r['verdicts'], 'package'));
    }

    public function test_an_extension_declaring_no_core_constraint_is_asked_about(): void
    {
        // Rare, and the honest response is to ask rather than to assume either way.
        $r = (new CoreUpgrade())->preflight([$this->ext('acme/loose', '1.0.0', null)], '2.1.0');

        $this->assertSame(['acme/loose'], $r['pending']);
        $this->assertNull($r['verdicts'][0]['requires']);
    }

    public function test_a_release_candidate_core_admits_a_caret_two_extension(): void
    {
        // Every Flarum 2 forum is on an rc today, and plain semver would call
        // 2.0.0-rc.8 less than 2.0.0 and block the lot.
        $r = (new CoreUpgrade())->preflight([$this->ext('acme/a', '1.0.0', '^2.0')], '2.0.0-rc.8');

        $this->assertSame('ready', $r['verdicts'][0]['state']);
    }
}
