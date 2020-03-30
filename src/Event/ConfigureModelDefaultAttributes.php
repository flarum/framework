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
 * @deprecated in beta 13, removed in beta 14
 */
class ConfigureModelDefaultAttributes
{
    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @param AbstractModel $model
     * @param array $attributes
     */
    public function __construct(AbstractModel $model, array &$attributes)
    {
        $this->model = $model;
        $this->attributes = &$attributes;
    }

    /**
     * @param string $model
     * @return bool
     */
    public function isModel($model)
    {
        return $this->model instanceof $model;
    }
}
