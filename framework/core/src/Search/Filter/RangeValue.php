<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

/**
 * Parses the `from..to` value of a range filter, where either side may be
 * empty (`2024-01-01..`, `..10`). A single value without `..` is a range of
 * that one value. Dates are `YYYY-MM-DD`; numbers must be finite.
 */
class RangeValue
{
    /**
     * @param 'date'|'number' $kind
     * @return array{0: string|null, 1: string|null}|null [from, to], or null when the value is malformed
     *                                                    or both sides are empty
     */
    public static function parse(string $value, string $kind = 'date'): ?array
    {
        $value = trim($value);
        $parts = str_contains($value, '..') ? explode('..', $value, 2) : [$value, $value];

        [$from, $to] = array_map(fn (string $part) => trim($part) === '' ? null : trim($part), $parts);

        if ($from === null && $to === null) {
            return null;
        }

        foreach ([$from, $to] as $side) {
            if ($side !== null && ! static::valid($side, $kind)) {
                return null;
            }
        }

        return [$from, $to];
    }

    protected static function valid(string $value, string $kind): bool
    {
        if ($kind === 'number') {
            return is_numeric($value) && is_finite((float) $value);
        }

        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
