<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Tree;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use PHPUnit\Framework\TestCase;

/** "Recently updated", as an app store keeps it: written from RunStore::save, kept past pruning. */
class HistoryTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-hist-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/runs/r1', 0775, true);
        file_put_contents($this->dir.'/runs/r1/plan.json', json_encode(['changes' => [['op' => 'replace', 'package' => 'acpl/mobile-tab', 'from' => '2.0.0', 'to' => '2.0.1']]]));
        file_put_contents($this->dir.'/runs/r1/requested.json', json_encode(['packages' => ['acpl/mobile-tab'], 'mode' => 'update']));
        file_put_contents($this->dir.'/runs/r1/migrations.json', json_encode([['migration' => '2026_01_01_000000_add_variants', 'extension' => 'acpl-mobile-tab']]));
    }

    protected function tearDown(): void
    {
        Tree::delete($this->dir);
    }

    public function test_a_finished_run_is_recorded_once_and_an_undo_updates_it(): void
    {
        $store = new RunStore($this->dir.'/runs');
        $run = Run::start('r1', 1000);

        $store->save($run);
        $this->assertSame([], $store->history(), 'a run still going is not history yet');

        $done = Run::fromArray(['state' => Run::DONE, 'movedAt' => 1100] + $run->toArray());
        $store->save($done);
        $store->save($done);   // saved again: still one entry

        $history = $store->history();
        $this->assertCount(1, $history);
        $this->assertSame('done', $history[0]['state']);
        $this->assertSame('2.0.1', $history[0]['changes'][0]['to']);
        $this->assertSame(1, $history[0]['migrations']);

        // Undo deletes the run's migrations.json and the pruner may take plan.json: the entry keeps both.
        unlink($this->dir.'/runs/r1/migrations.json');
        unlink($this->dir.'/runs/r1/plan.json');
        $store->save(Run::fromArray(['state' => Run::ROLLBACK, 'movedAt' => 1200] + $run->toArray()));

        $history = $store->history();
        $this->assertCount(1, $history);
        $this->assertSame('rolled-back', $history[0]['state']);
        $this->assertSame(1200, $history[0]['at']);
        $this->assertSame('acpl/mobile-tab', $history[0]['changes'][0]['package']);
        $this->assertSame(1, $history[0]['migrations']);
    }
}
