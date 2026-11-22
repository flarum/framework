<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Illuminate\Database\Eloquent\Model;

/**
 * Ids come from URLs and slugs, so they can be any text. Only digits can match
 * an integer key: MySQL reads "2abc" as 2, and PostgreSQL refuses to compare
 * it. PostgreSQL also refuses a number beyond the key column's range, so the
 * range is read from the column itself: `integer` and `bigint` keys both
 * report the `int` key type in Eloquent. Ids within the `integer` range skip
 * that read, so `smallint` keys are not covered.
 */
class IntegerKey
{
    private const INT_MAX = '2147483647';

    private const BIGINT_MAX = '9223372036854775807';

    public static function fits(Model $model, int|string $id): bool
    {
        return self::filter($model, [$id]) !== [];
    }

    /**
     * Reads the column type at most once, however many ids are given.
     *
     * @param  array<int|string>  $ids
     * @return array<int|string>
     */
    public static function filter(Model $model, array $ids): array
    {
        $ids = array_filter($ids, fn ($id) => ctype_digit((string) $id));

        if ($model->getConnection()->getDriverName() !== 'pgsql') {
            return $ids;
        }

        $outOfRange = array_filter($ids, fn ($id) => ! self::atMost((string) $id, self::INT_MAX));

        if ($outOfRange === []) {
            return $ids;
        }

        $max = match (self::columnType($model)) {
            'int4' => self::INT_MAX,
            'int8' => self::BIGINT_MAX,
            default => null,
        };

        return array_filter($ids, fn ($id) => $max === null || self::atMost((string) $id, $max));
    }

    /**
     * Reads the type through the connection's search_path, and quotes the
     * table as the query does, so a schema-qualified table resolves too.
     */
    private static function columnType(Model $model): ?string
    {
        $connection = $model->getConnection();
        $table = $connection->getQueryGrammar()->wrapTable($model->getTable());

        return $connection->selectOne(
            'select t.typname as type from pg_attribute a join pg_type t on t.oid = a.atttypid '
            .'where a.attrelid = to_regclass(?) and a.attname = ? and not a.attisdropped',
            [$table, $model->getKeyName()]
        )?->type;
    }

    /**
     * Compares the digits as text, as a float loses precision near the bigint
     * limit.
     */
    private static function atMost(string $digits, string $max): bool
    {
        $digits = ltrim($digits, '0') ?: '0';

        return strlen($digits) < strlen($max) || (strlen($digits) === strlen($max) && strcmp($digits, $max) <= 0);
    }
}
