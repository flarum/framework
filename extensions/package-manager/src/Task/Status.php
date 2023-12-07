<?php

namespace Flarum\PackageManager\Task;

enum Status: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case FAILURE = 'failure';
    case SUCCESS = 'success';
}
