<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Api\Controller\StartController;
use Flarum\ExtensionManager\Work\ComposerSteps;
use Flarum\ExtensionManager\Work\WorkDir;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Raising a pinned requirement so an update can actually happen.
 *
 * 🚨 The whole feature exists because pressing Update on a pinned package did
 * nothing and said Finished. It edits composer.json, so every rule here is
 * about making that edit small, expected, and reversible.
 */
class RepinTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-repin-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/extension-manager', 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/extension-manager/*') ?: [] as $f) {
            @unlink($f);
        }
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            is_dir($f) ? @rmdir($f) : @unlink($f);
        }
        @rmdir($this->dir);
    }

    // ── what the SERVER will allow ───────────────────────────────────────────

    /**
     * 🚨 The browser asks WHETHER. It never says to WHAT.
     *
     * The target comes from this site's own check, so a requirement can only be
     * moved to the version printed on the card the admin just read. A version
     * carried in the request body would be an arbitrary string from a client
     * written into composer.json.
     */
    public function test_the_target_version_comes_from_the_check_not_the_request(): void
    {
        $out = $this->repinFor(
            ['ernestdefoe/page-builder'],
            ['repin' => true, 'version' => '99.0.0', 'to' => '99.0.0'],
            ['ernestdefoe/page-builder' => '3.5.1'],
            ['ernestdefoe/page-builder' => ['from' => '3.5.1', 'to' => '3.6.0']]
        );

        $this->assertSame(['ernestdefoe/page-builder' => '3.6.0'], $out);
    }

    public function test_nothing_is_raised_unless_it_was_asked_for(): void
    {
        $out = $this->repinFor(
            ['ernestdefoe/page-builder'],
            [],
            ['ernestdefoe/page-builder' => '3.5.1'],
            ['ernestdefoe/page-builder' => ['from' => '3.5.1', 'to' => '3.6.0']]
        );

        $this->assertSame([], $out);
    }

    /** A range is a decision nobody asked to change. */
    public function test_a_range_constraint_is_never_rewritten(): void
    {
        foreach (['^3.5', '~3.5.1', '*', 'dev-main'] as $constraint) {
            $out = $this->repinFor(
                ['ernestdefoe/page-builder'],
                ['repin' => true],
                ['ernestdefoe/page-builder' => $constraint],
                ['ernestdefoe/page-builder' => ['from' => '3.5.1', 'to' => '3.6.0']]
            );

            $this->assertSame([], $out, "'$constraint' must be left alone.");
        }
    }

    /** A package with no newer version has nothing to be raised to. */
    public function test_a_package_the_check_knows_nothing_about_is_left_alone(): void
    {
        $out = $this->repinFor(['a/thing'], ['repin' => true], ['a/thing' => '1.0.0'], []);

        $this->assertSame([], $out);
    }

    /**
     * A forum that writes `v0.3.1` gets `v0.3.2`. A constraint that suddenly
     * changes shape reads as something nobody did on purpose.
     */
    public function test_it_keeps_the_sites_own_spelling(): void
    {
        $out = $this->repinFor(
            ['ernestdefoe/importer'],
            ['repin' => true],
            ['ernestdefoe/importer' => 'v0.3.1'],
            ['ernestdefoe/importer' => ['from' => 'v0.3.1', 'to' => '0.3.2']]
        );

        $this->assertSame(['ernestdefoe/importer' => 'v0.3.2'], $out);
    }

    // ── what the RUN actually writes ─────────────────────────────────────────

    public function test_it_raises_only_the_requirement_it_was_given(): void
    {
        $before = ['require' => ['a/pinned' => '1.0.0', 'b/other' => '2.0.0']];
        $after = $this->raise($before, ['a/pinned' => '1.1.0'], ['a/pinned']);

        $this->assertSame('1.1.0', $after['require']['a/pinned']);
        $this->assertSame('2.0.0', $after['require']['b/other'], 'Nothing else may be touched.');
    }

    /**
     * 🚨 Even with permission, only a package this run was asked about. A repin
     * map that named something else would otherwise edit a requirement the
     * admin never saw.
     */
    public function test_it_ignores_a_package_this_run_was_not_asked_about(): void
    {
        $before = ['require' => ['a/pinned' => '1.0.0']];
        $after = $this->raise($before, ['a/pinned' => '1.1.0'], ['something/else']);

        $this->assertSame('1.0.0', $after['require']['a/pinned']);
    }

    /**
     * 🚨 It raises to an EXACT version, never to a range. A site that pinned
     * 3.5.1 deliberately stays pinned at 3.6.0 — quietly converting it to ^3.6
     * would hand back the drift the pin existed to stop.
     */
    public function test_the_requirement_stays_exact_afterwards(): void
    {
        $after = $this->raise(['require' => ['a/pinned' => '3.5.1']], ['a/pinned' => '3.6.0'], ['a/pinned']);

        $this->assertSame('3.6.0', $after['require']['a/pinned']);
        $this->assertDoesNotMatchRegularExpression('/[\^~*]/', $after['require']['a/pinned']);
    }

    public function test_a_package_the_site_does_not_require_is_not_added(): void
    {
        $after = $this->raise(['require' => []], ['a/pinned' => '1.1.0'], ['a/pinned']);

        $this->assertArrayNotHasKey('a/pinned', $after['require']);
    }

    // ── and that the permission actually travels ─────────────────────────────

    /**
     * 🚨 A run outlives the request that started it, so the permission has to be
     * on disk with the package list. A worker picking this up an hour later must
     * raise the requirement the admin was SHOWN, not recompute it from a check
     * that may have been refreshed since.
     */
    public function test_the_permission_survives_being_written_down(): void
    {
        $work = new WorkDir($this->dir, 'r-test');
        $work->create()->remember(['a/pinned'], 'update', ['a/pinned' => '1.1.0']);

        $this->assertSame(['a/pinned'], (new WorkDir($this->dir, 'r-test'))->requested());
        $this->assertSame(['a/pinned' => '1.1.0'], (new WorkDir($this->dir, 'r-test'))->repin());
    }

    /** An older run, written before this existed, must simply have none. */
    public function test_a_run_recorded_without_a_permission_raises_nothing(): void
    {
        $work = new WorkDir($this->dir, 'r-old');
        $work->create()->remember(['a/pinned'], 'update');

        $this->assertSame([], (new WorkDir($this->dir, 'r-old'))->repin());
    }

    /**
     * 🚨 And the factory hands it to the steps.
     *
     * Twice today a correct piece of code sat unreached because nothing passed
     * it in — the health check for its whole life, and the pin explanation for
     * the length of one commit. Testing the rule is not testing that the rule
     * runs.
     */
    public function test_the_factory_passes_the_permission_to_the_steps(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/Work/ComposerStepsFactory.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$workDir->repin()',
            $source,
            'The steps would be built with no permission, so every repin would silently do nothing.'
        );
    }

    /** And the controller records it when the run starts. */
    public function test_the_controller_records_the_permission(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/Api/Controller/StartController.php');

        $this->assertIsString($source);
        // Nightly targets come from Plan\Nightly; every other run still asks repinFor().
        $this->assertMatchesRegularExpression('/remember\(\$packages, .*\$mode, \$nightly \?\? \$this->repinFor\(/', (string) $source);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** @return array<string,string> */
    private function repinFor(array $packages, array $body, array $require, array $updates): array
    {
        file_put_contents($this->dir.'/composer.json', json_encode(['require' => $require]));
        file_put_contents($this->dir.'/extension-manager/updates.json', json_encode([
            'checkedAt' => time(), 'updates' => $updates, 'uncheckable' => [], 'tracking' => [],
        ]));

        $reflection = new ReflectionClass(StartController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $paths = new \Flarum\Foundation\Paths([
            'base' => $this->dir, 'public' => $this->dir, 'storage' => $this->dir, 'vendor' => $this->dir,
        ]);
        $reflection->getProperty('paths')->setValue($controller, $paths);

        return $reflection->getMethod('repinFor')->invoke($controller, $packages, $body, 'update');
    }

    /** @return array<string,mixed> the composer.json as it is left */
    private function raise(array $composerJson, array $repin, array $requested): array
    {
        file_put_contents($this->dir.'/composer.json', json_encode($composerJson));

        $reflection = new ReflectionClass(ComposerSteps::class);
        $steps = $reflection->newInstanceWithoutConstructor();

        foreach ([
            'installPath' => $this->dir,
            'requested' => $requested,
            'mode' => 'update',
            'repin' => $repin,
        ] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($steps, $value);
        }

        $reflection->getMethod('raisePins')->invoke($steps);

        return json_decode((string) file_get_contents($this->dir.'/composer.json'), true);
    }
}
