<?php

namespace Flarum\PackageManager\Job;

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
