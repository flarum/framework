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
    private $steps;

    /**
     * @var callable[]
     */
    private $callbacks;

    /**
     * @var SplStack
     */
    private $successfulSteps;

    public function __construct(array $steps = [])
    {
        $this->steps = $steps;
    }

    public function pipe(callable $factory)
    {
        $this->steps[] = $factory;

        return $this;
    }

    public function on($event, callable $callback)
    {
        $this->callbacks[$event] = $callback;

        return $this;
    }

    public function run()
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
     * @param callable $factory
     * @throws StepFailed
     */
    private function runStep(callable $factory)
    {
        /** @var Step $step */
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

    private function revertReversibleSteps()
    {
        foreach ($this->successfulSteps as $step) {
            if ($step instanceof ReversibleStep) {
                $this->fireCallbacks('rollback', $step);

                $step->revert();
            }
        }
    }

    private function fireCallbacks($event, Step $step)
    {
        if (isset($this->callbacks[$event])) {
            ($this->callbacks[$event])($step);
        }
    }
}
