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

class RegisterPostTypes
{
    protected $models;

    public function __construct(array &$models)
    {
        $this->models = &$models;
    }

    public function register($class)
    {
        $this->models[] = $class;
    }
}
