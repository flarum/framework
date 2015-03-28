<?php namespace Flarum\Core\Models;

use Illuminate\Database\Eloquent\ScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RegisteredTypesScope implements ScopeInterface
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereIn('type', array_keys($model::getTypes()));
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function remove(Builder $builder, Model $model)
    {
        $query = $builder->getQuery();

        foreach ((array) $query->wheres as $key => $where)
        {
            if ($this->isTypeConstraint($where))
            {
                unset($query->wheres[$key]);

                $query->wheres = array_values($query->wheres);
            }
        }
    }

    /**
     * Determine if the given where clause is a type constraint.
     *
     * @param  array   $where
     * @param  string  $column
     * @return bool
     */
    protected function isTypeConstraint(array $where)
    {
        return $where['type'] == 'In' && $where['column'] == 'type';
    }

}
