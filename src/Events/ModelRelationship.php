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

use Flarum\Core\Model;

/**
 * The `ModelRelationship` event is called to retrieve Relation object for a
 * model. Listeners should return an Eloquent Relation object.
 */
class ModelRelationship
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @param Model $model
     * @param string $relationship
     */
    public function __construct(Model $model, $relationship)
    {
        $this->model = $model;
        $this->relationship = $relationship;
    }
}
