<?php namespace Flarum\Core\Models;

use Illuminate\Database\Eloquent\ScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class RegisteredTypesScope implements ScopeInterface
{
    /**
     * The index at which we added a where clause.
     *
     * @var int
     */
    protected $whereIndex;

    /**
     * The index at which we added where bindings.
     *
     * @var int
     */
    protected $bindingIndex;

    /**
     * The number of where bindings we added.
     *
     * @var int
     */
    protected $bindingCount;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Eloquent $model)
    {
        $query = $builder->getQuery();

        $this->whereIndex = count($query->wheres);
        $this->bindingIndex = count($query->getRawBindings()['where']);

        $types = array_keys($model::getTypes());
        $this->bindingCount = count($types);
        $query->whereIn('type', $types);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function remove(Builder $builder, Eloquent $model)
    {
        $query = $builder->getQuery();

        unset($query->wheres[$this->whereIndex]);
        $query->wheres = array_values($query->wheres);

        $whereBindings = $query->getRawBindings()['where'];
        array_splice($whereBindings, $this->bindingIndex, $this->bindingCount);
        $query->setBindings(array_values($whereBindings));
    }
}
