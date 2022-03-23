<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\MySqlConnection;

trait InsertsViaSubqueryTrait
{
    /**
     * A list that maps attribute names to callables that produce subqueries
     * used to calculate the inserted value.
     *
     * Each callable should take an instance of the model being saved,
     * and return an Eloquent query builder that queries for the subquery
     * generated value. The result of the query should be one row of one column.
     *
     * Subquery attributes should be added in the static `boot` method of models
     * using this trait.
     *
     * @var array<string, callable(AbstractModel): Builder>
     */
    protected static $subqueryAttributes = [];

    /**
     * Overriden so that some fields can be inserted via subquery.
     * The goal here is to construct a subquery that returns primitives
     * for the provided `attributes`, and uses additional subqueries for
     * statically-specified subqueryAttributes.
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $subqueryAttrNames = array_keys(static::$subqueryAttributes);

        $literalAttributes = array_diff_key($attributes, array_flip($subqueryAttrNames));

        /** @var Builder */
        $insertRowSubquery = static::query()->limit(1);

        foreach ($literalAttributes as $attrName => $value) {
            $parameter = $query->getGrammar()->parameter($value);
            $insertRowSubquery->addBinding($value, 'select');
            $insertRowSubquery->selectRaw("$parameter as $attrName");
        }

        foreach (static::$subqueryAttributes as $attrName => $callback) {
            $insertRowSubquery->selectSub($callback($this), $attrName);
        }

        $attrNames = array_merge(array_keys($literalAttributes), $subqueryAttrNames);
        $query->insertUsing($attrNames, $insertRowSubquery);

        // This should be accurate, as it's the same mechanism used by Laravel's `insertGetId`.
        // See https://github.com/laravel/framework/blob/master/src/Illuminate/Database/Query/Processors/Processor.php#L30-L37.
        /** @var MySqlConnection */
        $con = $query->getQuery()->getConnection();
        $idRaw = $con->getPdo()->lastInsertId($keyName = $this->getKeyName());
        $id = is_numeric($idRaw) ? (int) $idRaw : $idRaw;

        $this->setAttribute($keyName, $id);

        // This is necessary to get the computed value of saved attributes.
        $this->exists = true;
        $this->refresh();
    }
}
