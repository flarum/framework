<?php namespace Flarum\Events;

use Flarum\Core\Model;
use Flarum\Core\Users\User;

/**
 * The `ModelAllow` event
 */
class ModelAllow
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var array
     */
    public $actor;

    /**
     * @var string
     */
    public $action;

    /**
     * @param Model $model
     * @param User $actor
     * @param $action
     */
    public function __construct(Model $model, User $actor, $action)
    {
        $this->model = $model;
        $this->actor = $actor;
        $this->action = $action;
    }
}
