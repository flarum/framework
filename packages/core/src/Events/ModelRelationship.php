<?php namespace Flarum\Events;

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
