<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Job;

class DispatcherResponse
{
    public function __construct(
        public bool $queueJobs,
        public ?array $data
    ) {
    }
}
