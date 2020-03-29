<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

/**
 * @deprecated in beta 13, remove in beta 14. Use the Post extender instead.
 */
class ConfigurePostTypes
{

    public function __construct(array &$models)
    {
        $this->models = &$models;
    }

    public function add($model)
    {
        Post::setModel($model::$type, $model);
    }
}
