<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Job;

class DispatcherResponse
{
    public $queueJobs;

    public $data;

    public function __construct(bool $queueJobs, ?array $data)
    {
        $this->queueJobs = $queueJobs;
        $this->data = $data;
    }
}
