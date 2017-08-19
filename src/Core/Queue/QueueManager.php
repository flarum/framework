<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Queue;

use Illuminate\Queue\QueueManager as Manager;

class QueueManager extends Manager
{
    /**
     * {@inheritdoc}
     */
    protected function getConfig($name)
    {
        if ($name === 'sync') {
            return ['driver' => 'sync'];
        }

        return parent::getConfig($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return 'sync';
    }
}
