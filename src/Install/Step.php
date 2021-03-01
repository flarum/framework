<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

interface Step
{
    /**
     * A one-line status message summarizing what's happening in this step.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Do the work that constitutes this step.
     *
     * This method should raise a `StepFailed` exception whenever something goes
     * wrong that should result in the entire installation being reverted.
     *
     * @return void
     * @throws StepFailed
     */
    public function run();
}
