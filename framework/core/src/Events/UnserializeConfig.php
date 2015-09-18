<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

class UnserializeConfig
{
    /**
     * The config array.
     *
     * @var array
     */
    public $config;

    /**
     * @param array $config The config array.
     */
    public function __construct(&$config)
    {
        $this->config = &$config;
    }
}
