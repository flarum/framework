<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Carbon\Carbon;
use Flarum\Foundation\ValidationException as FlarumValidationException;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies a `from..to` range filter value (see `RangeValue`) to a column.
 * Date bounds are inclusive days, applied as half-open datetime bounds
 * (`>= from 00:00:00`, `< to+1 00:00:00`) so an index on the column is used.
 * A negated range matches rows outside the range and rows where the column is
 * null ("never set"). A malformed value is a validation error, like the other
 * filter type checks.
 */
trait FiltersByRange
{
    /**
     * @param 'date'|'number' $kind
     */
    protected function constrainRange(Builder $query, string $column, string|array $value, bool $negate, string $kind = 'date'): void
    {
        $range = is_array($value) ? null : RangeValue::parse(trim($value, '"'), $kind);

        if ($range === null) {
            throw new FlarumValidationException([
                'message' => resolve(TranslatorInterface::class)->trans('core.api.invalid_filter_type.must_be_range_message', ['{filter}' => $this->getFilterKey()]),
            ]);
        }

        [$from, $to] = $range;

        $constrain = function (Builder $query) use ($column, $from, $to, $kind) {
            if ($from !== null) {
                $query->where($column, '>=', $kind === 'date' ? "$from 00:00:00" : (float) $from);
            }

            if ($to !== null) {
                $kind === 'date'
                    ? $query->where($column, '<', Carbon::parse($to)->addDay()->format('Y-m-d 00:00:00'))
                    : $query->where($column, '<=', (float) $to);
            }
        };

        if ($negate) {
            $query->where(fn (Builder $query) => $query->whereNot($constrain)->orWhereNull($column));
        } else {
            $query->where($constrain);
        }
    }

    /**
     * @param 'date'|'number' $kind
     * @return array{kind: 'date'|'number', range: true, negatable: true, schema: array<string, string>}
     */
    protected function describeRange(string $kind = 'date'): array
    {
        return [
            'kind' => $kind,
            'range' => true,
            'negatable' => true,
            'schema' => $kind === 'date' ? ['type' => 'string', 'format' => 'date'] : ['type' => 'number'],
        ];
    }
}
