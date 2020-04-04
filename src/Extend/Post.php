<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Post\Post as ActualPost;
use Illuminate\Contracts\Container\Container;

class Post implements ExtenderInterface
{
    public function type($postType)
    {
        ActualPost::setModel($postType::$type, $postType);

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        // Nothing happens here, type does all the work.
    }
}
