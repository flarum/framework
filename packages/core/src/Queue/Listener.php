<?php

namespace Flarum\Queue;

use Illuminate\Queue\ListenerOptions;

class Listener extends \Illuminate\Queue\Listener
{
    protected function addEnvironment($command, ListenerOptions $options)
    {
        $options->environment = null;

        return $command;
    }
}
