<?php namespace Flarum\Events;

use Flarum\Core\Model;
use Flarum\Core\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopeModelVisibility` event allows constraints to be applied in a query
 * to fetch a model, effectively scoping that model's visibility to the user.
 */
class ScopeModelVisibility
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Model $model
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Model $model, Builder $query, User $actor)
    {
        $this->model = $model;
        $this->query = $query;
        $this->actor = $actor;
    }
}
