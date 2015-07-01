<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Closure;

class Model implements ExtenderInterface
{
    protected $model;

    protected $scopeVisible = [];

    protected $allow = [];

    protected $relations = [];

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function scopeVisible(Closure $callback)
    {
        $this->scopeVisible[] = $callback;

        return $this;
    }

    public function allow($action, Closure $callback)
    {
        $this->allow[] = compact('action', 'callback');

        return $this;
    }

    public function date($attribute)
    {
        $this->dates[] = $attribute;
    }

    public function hasOne($relation, $related, $foreignKey = null, $localKey = null)
    {
        $this->relations[$relation] = function ($model) use ($relation, $related, $foreignKey, $localKey) {
            return $model->hasOne($related, $foreignKey, $localKey, $relation);
        };

        return $this;
    }

    public function belongsTo($relation, $related, $foreignKey = null, $otherKey = null)
    {
        $this->relations[$relation] = function ($model) use ($relation, $related, $foreignKey, $otherKey) {
            return $model->belongsTo($related, $foreignKey, $otherKey, $relation);
        };

        return $this;
    }

    public function hasMany($relation, $related, $foreignKey = null, $localKey = null)
    {
        $this->relations[$relation] = function ($model) use ($relation, $related, $foreignKey, $localKey) {
            return $model->hasMany($related, $foreignKey, $localKey, $relation);
        };

        return $this;
    }

    public function belongsToMany($relation, $related, $table = null, $foreignKey = null, $otherKey = null)
    {
        $this->relations[$relation] = function ($model) use ($relation, $related, $table, $foreignKey, $otherKey) {
            return $model->belongsToMany($related, $table, $foreignKey, $otherKey, $relation);
        };

        return $this;
    }

    public function extend(Container $container)
    {
        $model = $this->model;

        foreach ($this->relations as $relation => $callback) {
            $model::setRelationMethod($relation, $callback);
        }

        foreach ($this->scopeVisible as $callback) {
            $model::scopeVisible($callback);
        }

        foreach ($this->allow as $info) {
            $model::allow($info['action'], $info['callback']);
        }

        foreach ($this->dates as $attribute) {
            $model::addDate($attribute);
        }
    }
}
