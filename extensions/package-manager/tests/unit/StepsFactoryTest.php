<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\ExtensionManager\Run\StepsFactory;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule this protects: the work belongs to the run, not to the process.
 *
 * Under PHP-FPM nothing survives a request, so a container that resolves "the
 * current run" once is right by accident. A queue worker is a long-lived
 * process where that accident stops holding — and the failure is quiet, because
 * everything still succeeds. It is the JOURNAL that ends up wrong, and the
 * journal is only read when somebody rolls back, by which time the run that
 * wrote it is long over.
 */
class StepsFactoryTest extends TestCase
{
    private string $dir;
    private int $clock = 1000;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-factory-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            unlink($f);
        }
        @rmdir($this->dir);
    }

    public function test_each_run_gets_work_built_for_that_run(): void
    {
        $factory = $this->recordingFactory();
        $runner = new StepRunner(new RunStore($this->dir), $factory, fn () => $this->clock);

        $runner->begin('run-a');
        $runner->step('run-a');
        $runner->step('run-a');

        $this->assertSame(['run-a'], array_unique($factory->askedFor));
    }

    public function test_a_second_run_in_the_same_process_does_not_inherit_the_first(): void
    {
        /*
         * The worker case, reproduced: one process, two runs, one container.
         * Before the factory this passed the SECOND run's items to the FIRST
         * run's staging directory and journal — so the record said run A had
         * moved files that run B moved, and a rollback of either restored the
         * wrong ones.
         */
        $factory = $this->recordingFactory();
        $store = new RunStore($this->dir);
        $runner = new StepRunner($store, $factory, fn () => $this->clock);

        $runner->begin('run-a');
        while (! $store->load('run-a')?->isFinished()) {
            $runner->step('run-a');
        }

        $runner->begin('run-b');
        $runner->step('run-b');
        $runner->step('run-b');

        $this->assertContains('run-a', $factory->askedFor);
        $this->assertContains('run-b', $factory->askedFor);

        $this->assertSame(
            ['run-b'],
            array_values(array_unique(array_map(
                fn (array $row) => $row['run'],
                array_filter($factory->work, fn (array $row) => $row['item'] === 'b-item')
            ))),
            "run B's work must not be done through run A's apparatus"
        );
    }

    public function test_a_plain_steps_object_still_works(): void
    {
        // Kept because the resumability and crash tests supply deterministic
        // work that has no run of its own, and rewriting them to satisfy a
        // container detail would test the container rather than the recovery.
        $steps = new class implements Steps {
            public function itemsFor(string $phase, Run $run): array
            {
                return $phase === 'plan' ? ['only'] : [];
            }

            public function doItem(string $phase, string $item, Run $run): ?string
            {
                return 'did it';
            }
        };

        $runner = new StepRunner(new RunStore($this->dir), $steps, fn () => $this->clock);
        $runner->begin('r1');
        $runner->step('r1');
        $run = $runner->step('r1');

        $this->assertContains('did it', $run->log);
    }

    private function recordingFactory(): object
    {
        return new class implements StepsFactory {
            /** @var list<string> */
            public array $askedFor = [];
            /** @var list<array{run:string,item:string}> */
            public array $work = [];

            public function for(string $runId): Steps
            {
                $this->askedFor[] = $runId;

                return new class($runId, $this) implements Steps {
                    public function __construct(private string $runId, private object $parent)
                    {
                    }

                    public function itemsFor(string $phase, Run $run): array
                    {
                        // Each run has its own item name, so work done through
                        // the wrong apparatus is visible rather than plausible.
                        $mine = str_contains($this->runId, 'run-b') ? 'b-item' : 'a-item';

                        return $phase === 'plan' ? [$mine] : [];
                    }

                    public function doItem(string $phase, string $item, Run $run): ?string
                    {
                        $this->parent->work[] = ['run' => $this->runId, 'item' => $item];

                        return "$item done";
                    }
                };
            }
        };
    }
}
