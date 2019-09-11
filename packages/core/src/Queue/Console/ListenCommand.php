<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Illuminate\Queue\Listener;

class ListenCommand extends \Illuminate\Queue\Console\ListenCommand
{
    public function __construct(Listener $listener)
    {
        parent::__construct($listener);

        $this->addOption('env');
    }
}
