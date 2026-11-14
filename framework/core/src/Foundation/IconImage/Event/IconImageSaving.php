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
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Dispatched before a model's icon image is stored or removed through the
 * icon image endpoints, which don't run the resource's own saving hooks. A
 * listener can throw to stop the change, or alter the image before it is
 * stored. Check `$model` to act on one kind of model only.
 */
class IconImageSaving
{
    public function __construct(
        public AbstractModel $model,
        public User $actor,
        /** The uploaded image, or null when the image is being removed. */
        public ?ImageInterface $image
    ) {
    }
}
