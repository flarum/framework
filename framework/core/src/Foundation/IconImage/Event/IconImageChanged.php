<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage\Event;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * Dispatched once a model's new icon image, or its removal, has been saved
 * through the icon image endpoints. `$model->icon_image_path` is null when the
 * image was removed. The change is already stored when it is dispatched: a
 * listener that throws fails the request but doesn't undo it.
 */
class IconImageChanged
{
    public function __construct(
        public AbstractModel $model,
        public User $actor
    ) {
    }
}
