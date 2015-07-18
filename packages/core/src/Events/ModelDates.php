<?php namespace Flarum\Events;

use Flarum\Core\Model;

/**
 * The `ModelDates` event is called to retrieve a list of fields for a model
 * that should be converted into date objects.
 */
class ModelDates
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var array
     */
    public $dates;

    /**
     * @param Model $model
     * @param array $dates
     */
    public function __construct(Model $model, array &$dates)
    {
        $this->model = $model;
        $this->dates = &$dates;
    }
}
