<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Database\AbstractModel;

/**
 * @deprecated beta 16, remove beta 17.
 *
 * Determine whether or not a model should be marked as `is_private`.
 */
class GetModelIsPrivate
{
    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @param AbstractModel $model
     */
    public function __construct(AbstractModel $model)
    {
        $this->model = $model;
    }
}
