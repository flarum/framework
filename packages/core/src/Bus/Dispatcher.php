<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Bus;

use Illuminate\Bus\Dispatcher as BaseDispatcher;

class Dispatcher extends BaseDispatcher
{
    public function getCommandHandler($command)
    {
        $handler = get_class($command).'Handler';

        if (class_exists($handler)) {
            return $this->container->make($handler);
        }

        return parent::getCommandHandler($command);
    }
}
