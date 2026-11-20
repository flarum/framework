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
 * report the `int` key type in Eloquent.
 */
class IntegerKey
{
    private const INT_MAX = '2147483647';

    private const BIGINT_MAX = '9223372036854775807';

    public static function fits(Model $model, int|string $id): bool
    {
        $id = (string) $id;

        if (! ctype_digit($id)) {
            return false;
        }

        if ($model->getConnection()->getDriverName() !== 'pgsql' || self::atMost($id, self::INT_MAX)) {
            return true;
        }

        return match ($model->getConnection()->getSchemaBuilder()->getColumnType($model->getTable(), $model->getKeyName())) {
            'int2', 'int4' => false,
            'int8' => self::atMost($id, self::BIGINT_MAX),
            default => true,
        };
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
