<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Database\AbstractModel;

/**
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
