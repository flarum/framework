<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\ComposerSteps;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * What an update is allowed to move, and what it says when it moves nothing.
 *
 * 🚨 Both of these were found by running a real update on a real forum and
 * reading what happened, not from the code.
 */
class UpdateScopeTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-scope-'.bin2hex(random_bytes(6));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    /**
     * 🚨 `--with-all-dependencies` also updates ROOT requirements.
     *
     * Asking the Extension Manager to update two extensions on ernestdefoe.online moved
     * TWENTY-FIVE packages — the whole illuminate stack, commonmark, monolog —
     * and did not move either extension. Nobody chose that, and on a forum that
     * had just been deliberately pinned it was exactly what the pinning existed
     * to prevent.
     */
    public function test_an_update_does_not_move_root_requirements(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/Work/ComposerSteps.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("'--with-dependencies'", $source);
        $this->assertStringNotContainsString(
            "'--with-all-dependencies'",
            $source,
            '-W re-resolves packages the admin has deliberately fixed. Use -w.'
        );
    }

    /**
     * 🚨 "Everything is already at the newest version it can be" is true and
     * useless when a pin is the reason.
     *
     * edonline pinned page-builder to 3.5.1. The check correctly said 3.6.0
     * exists. Pressing Update ran a resolve that could not move it, reported
     * Finished, and left the card still offering the update — with nothing
     * anywhere saying why. The next thing somebody does is press it again.
     */
    public function test_it_names_the_pin_that_blocked_the_update(): void
    {
        $message = $this->whyNothingMoved(
            ['require' => ['ernestdefoe/page-builder' => '3.5.1']],
            ['ernestdefoe/page-builder']
        );

        $this->assertStringContainsString('page-builder is pinned to 3.5.1', $message);
        $this->assertStringContainsString('cannot be installed until that requirement is changed', $message);
    }

    /**
     * 🚨 And the update path actually CALLS it.
     *
     * The first version of this test exercised whyNothingMoved() directly and
     * passed while the update branch still returned the old useless sentence —
     * the same shape of hole that let the health check sit disconnected for its
     * whole life. Testing a function is not testing that anything reaches it.
     */
    public function test_the_update_branch_uses_that_explanation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/Work/ComposerSteps.php');

        $this->assertIsString($source);
        $this->assertMatchesRegularExpression(
            "/default\s*=>\s*\\\$this->whyNothingMoved\(\)/",
            $source,
            'An update that moves nothing must explain why, not repeat the stock sentence.'
        );
    }

    public function test_a_range_is_not_reported_as_a_pin(): void
    {
        foreach (['^3.5', '~3.5.1', '*', '>=3.0 <4.0'] as $constraint) {
            $message = $this->whyNothingMoved(
                ['require' => ['ernestdefoe/page-builder' => $constraint]],
                ['ernestdefoe/page-builder']
            );

            $this->assertStringContainsString(
                'already at the newest version',
                $message,
                "A '$constraint' constraint genuinely can be already-newest."
            );
        }
    }

    /** A `v` prefix is still a pin. */
    public function test_a_v_prefixed_exact_version_is_a_pin(): void
    {
        $message = $this->whyNothingMoved(
            ['require' => ['ernestdefoe/importer' => 'v0.3.1']],
            ['ernestdefoe/importer']
        );

        $this->assertStringContainsString('importer is pinned to v0.3.1', $message);
    }

    public function test_several_pins_are_all_named(): void
    {
        $message = $this->whyNothingMoved(
            ['require' => ['a/one' => '1.0.0', 'a/two' => '2.0.0']],
            ['a/one', 'a/two']
        );

        $this->assertStringContainsString('a/one is pinned to 1.0.0', $message);
        $this->assertStringContainsString('a/two is pinned to 2.0.0', $message);
    }

    private function whyNothingMoved(array $composerJson, array $requested): string
    {
        file_put_contents($this->dir.'/composer.json', json_encode($composerJson));

        $reflection = new ReflectionClass(ComposerSteps::class);
        $steps = $reflection->newInstanceWithoutConstructor();

        foreach (['installPath' => $this->dir, 'requested' => $requested, 'mode' => 'update'] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($steps, $value);
        }

        return $reflection->getMethod('whyNothingMoved')->invoke($steps);
    }
}
