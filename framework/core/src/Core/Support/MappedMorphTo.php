<?php namespace Flarum\Core\Support;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MappedMorphTo extends MorphTo
{

    /**
     *
     * @var string
     */
    protected $map;

    /**
     * Create a new morph to relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $type
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, $type, $relation, $map)
    {
        $this->map = $map;

        parent::__construct($query, $parent, $foreignKey, $otherKey, $type, $relation);
    }

    /**
     * Create a new model instance by type.
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModelByType($type)
    {
        return new $this->map[$type];
    }
}
