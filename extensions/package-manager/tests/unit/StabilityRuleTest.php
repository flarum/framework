<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Composer\Package\Loader\RootPackageLoader;
use Flarum\ExtensionManager\Config\StabilityRule;
use PHPUnit\Framework\TestCase;

/**
 * Which releases a forum's composer.json lets Composer install. The rule is
 * Composer's, so it is checked against Composer's own reading of the same
 * requirements.
 */
class StabilityRuleTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/mw-stability-'.bin2hex(random_bytes(6)).'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    private function rule(array $composerJson): StabilityRule
    {
        file_put_contents($this->file, json_encode($composerJson));

        return StabilityRule::fromComposerJson($this->file);
    }

    public function test_a_stable_forum_allows_only_stable_releases(): void
    {
        $rule = $this->rule(['minimum-stability' => 'stable']);

        $this->assertTrue($rule->allows('a/b', '1.0.0'));
        $this->assertTrue($rule->allows('a/b', 'v1.0.0'));
        $this->assertTrue($rule->allows('a/b', '1.0.0-p1'), 'a patch release is stable');
        $this->assertFalse($rule->allows('a/b', '1.0.0-RC1'));
        $this->assertFalse($rule->allows('a/b', '1.0.0-rc.1'));
        $this->assertFalse($rule->allows('a/b', '2.0.0-b2'), 'b2 is a beta');
        $this->assertFalse($rule->allows('a/b', '2.0.0-a1'), 'a1 is an alpha');
    }

    public function test_no_composer_json_is_composers_default_of_stable(): void
    {
        $rule = StabilityRule::fromComposerJson($this->file.'.missing');

        $this->assertTrue($rule->allows('a/b', '1.0.0'));
        $this->assertFalse($rule->allows('a/b', '1.0.0-RC1'));
        $this->assertFalse($rule->prefersStable());
    }

    public function test_minimum_stability_is_read_in_any_case(): void
    {
        $rule = $this->rule(['minimum-stability' => 'rc', 'prefer-stable' => true]);

        $this->assertTrue($rule->allows('a/b', '1.0.0-RC1'));
        $this->assertFalse($rule->allows('a/b', '1.0.0-beta.1'));
        $this->assertTrue($rule->prefersStable());
    }

    /**
     * Each requirement's own flag, compared with what Composer itself works
     * out, at every minimum-stability.
     */
    public function test_a_requirements_own_flag_is_read_as_composer_reads_it(): void
    {
        $requires = [
            'a/a' => '^2.0.0-beta.3',
            'b/b' => '^2.0@beta',
            'c/c' => '2.0.0-RC1',
            'd/d' => '^1.0 || ^2.0.0-alpha.1',
            'e/e' => '^2.0',
            'f/f' => 'dev-main as 1.0.0',
            'g/g' => '>=1.0 <2.0-beta.1',
            'h/h' => '^1.0@dev || ^2.0@RC',
            'i/i' => '1.0.0-b2',
            'j/j' => 'v2.0.0-beta.4',
            'k/k' => '^1.0@stable',
        ];
        $ranks = ['stable' => 0, 'RC' => 5, 'beta' => 10, 'alpha' => 15, 'dev' => 20];
        $versions = ['1.0.0', '1.0.0-RC1', '1.0.0-beta.1', '1.0.0-alpha.1', '1.0.0-dev'];

        foreach ($ranks as $minimum => $rank) {
            $rule = $this->rule(['minimum-stability' => $minimum, 'require' => $requires]);
            $flags = RootPackageLoader::extractStabilityFlags($requires, $minimum, []);

            foreach (array_keys($requires) as $package) {
                foreach ($versions as $version) {
                    $composer = StabilityRule::rank($version) <= max($rank, $flags[$package] ?? 0);

                    $this->assertSame($composer, $rule->allows($package, $version), "$package $version at $minimum");
                }
            }
        }
    }
}
