<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Work\ComposerStepsFactory;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 A plan that moved nothing does not migrate, publish assets or clear
 * caches. On the live box (2026-10-13) such a run went through every finalise
 * step and looked exactly like an update that happened.
 */
class NothingMovedTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-nothing-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/storage/extension-manager/runs/r1', 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_an_empty_plan_has_nothing_to_finalise(): void
    {
        $this->plan([]);

        $this->assertSame([], $this->finaliseItems());
    }

    public function test_a_plan_that_moves_something_is_finalised(): void
    {
        $this->plan([['op' => 'replace', 'package' => 'acme/widget', 'from' => '1.0.0', 'to' => '2.0.0']]);

        $this->assertContains('migrations', $this->finaliseItems());
    }

    /** No plan written at all is not "nothing moved": the steps run as before. */
    public function test_a_run_without_a_plan_file_is_finalised(): void
    {
        $this->assertContains('caches', $this->finaliseItems());
    }

    /** @param list<array<string,string>> $changes */
    private function plan(array $changes): void
    {
        file_put_contents($this->dir.'/storage/extension-manager/runs/r1/plan.json', json_encode(['changes' => $changes]));
    }

    /** @return list<string> */
    private function finaliseItems(): array
    {
        $paths = new Paths([
            'base' => $this->dir, 'public' => $this->dir.'/public',
            'storage' => $this->dir.'/storage', 'vendor' => $this->dir.'/vendor',
        ]);

        return (new ComposerStepsFactory($paths, new Config(['url' => 'https://example.test'])))
            ->for('r1')->itemsFor('finalise', Run::start('r1', time()));
    }
}
