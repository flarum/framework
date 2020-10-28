<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

/**
 * @deprecated in beta 15, remove in beta 16. Use the Post extender instead.
 */
class ConfigurePostTypes
{
    private $models;

    public function __construct(array &$models)
    {
        $this->models = &$models;
    }

    public function add($class)
    {
        $this->models[] = $class;
    }
}
