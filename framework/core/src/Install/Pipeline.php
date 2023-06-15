<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Exception;
use SplStack;

class Pipeline
{
    /**
     * @var callable[]
     */
    private array $callbacks;
    private SplStack $successfulSteps;

    public function __construct(
        /** @var callable[] */
        private array $steps = []
    ) {
    }

    public function pipe(callable $factory): self
    {
        $this->steps[] = $factory;

        return $this;
    }

    public function on(string $event, callable $callback): self
    {
        $this->callbacks[$event] = $callback;

        return $this;
    }

    public function run(): void
    {
        $this->successfulSteps = new SplStack;

        try {
            foreach ($this->steps as $factory) {
                $this->runStep($factory);
            }
        } catch (StepFailed $failure) {
            $this->revertReversibleSteps();

            throw $failure;
        }
    }

    /**
     * @param callable(): Step $factory
     * @throws StepFailed
     */
    private function runStep(callable $factory): void
    {
        $step = $factory();

        $this->fireCallbacks('start', $step);

        try {
            $step->run();
            $this->successfulSteps->push($step);

            $this->fireCallbacks('end', $step);
        } catch (Exception $e) {
            $this->fireCallbacks('fail', $step);

            throw new StepFailed('Step failed', 0, $e);
        }
    }

    private function revertReversibleSteps(): void
    {
        foreach ($this->successfulSteps as $step) {
            if ($step instanceof ReversibleStep) {
                $this->fireCallbacks('rollback', $step);

                $step->revert();
            }
        }
    }

    private function fireCallbacks(string $event, Step $step): void
    {
        if (isset($this->callbacks[$event])) {
            ($this->callbacks[$event])($step);
        }
    }
}
