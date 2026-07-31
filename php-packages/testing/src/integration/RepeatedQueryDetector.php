<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

/**
 * Spots N+1 query patterns in the queries a request ran.
 *
 * An N+1 is one query shape executed many times with different values — a
 * relationship loaded per model, a permission check per row, a serializer
 * callback hitting the database for each item. Grouping the query log by
 * normalised SQL surfaces them: legitimate work uses a handful of distinct
 * shapes, while an N+1 repeats one shape as many times as there are records.
 *
 * Bindings are counted, not folded into the shape. Two executions of the same
 * SQL with the *same* bindings are usually memoisation the caller could have
 * done, but they don't grow with the data; a shape repeated with *different*
 * bindings is the real N+1 signal. Reporting them separately is what stops
 * "the same SQL text appeared 4 times" from being mistaken for an N+1 when the
 * queries were for four different users.
 */
class RepeatedQueryDetector
{
    /**
     * Does this repeated shape represent work that grows with the data?
     *
     * One query per record — the same SQL run once for each of many different
     * values — is an N+1: add rows and you add queries. Repetitions of the
     * same few values are merely wasteful: they don't multiply as a forum
     * grows. Both are worth knowing about, but only the first is a defect that
     * gets worse over time.
     *
     * @param array{count: int, distinctBindings: int} $repeat
     */
    public static function scalesWithData(array $repeat): bool
    {
        // Allow one duplicate: batch loaders and permission checks often fetch
        // one value twice while still being per-record work.
        return $repeat['distinctBindings'] >= $repeat['count'] - 1;
    }

    /**
     * @param array<array{query: string, bindings: array}> $queries
     * @param int $threshold Repetitions of one shape before it is reported.
     * @return array<array{sql: string, count: int, distinctBindings: int}>
     */
    public static function findRepeats(array $queries, int $threshold): array
    {
        $shapes = [];

        foreach ($queries as $query) {
            $sql = $query['query'] ?? '';

            if ($sql === '' || ! self::isWorthCounting($sql)) {
                continue;
            }

            $shape = self::normalise($sql);

            if (! isset($shapes[$shape])) {
                $shapes[$shape] = ['sql' => $sql, 'count' => 0, 'bindings' => []];
            }

            $shapes[$shape]['count']++;
            $shapes[$shape]['bindings'][json_encode($query['bindings'] ?? [])] = true;
        }

        $repeats = [];

        foreach ($shapes as $shape) {
            if ($shape['count'] < $threshold) {
                continue;
            }

            $repeats[] = [
                'sql' => $shape['sql'],
                'count' => $shape['count'],
                'distinctBindings' => count($shape['bindings']),
            ];
        }

        usort($repeats, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $repeats;
    }

    /**
     * Reduce a query to its shape: values that vary between executions of the
     * same code path are replaced, so `id in (1, 2, 3)` and `id in (4, 5)`
     * count as one shape.
     */
    public static function normalise(string $sql): string
    {
        // Collapse IN lists (both placeholders and inlined values) first, so
        // batched loads of different sizes are recognised as the same shape.
        $sql = preg_replace('/\bin\s*\([^()]*\)/i', 'in (?)', $sql);

        // Inlined numbers and quoted strings.
        $sql = preg_replace('/\b\d+\b/', '?', $sql);
        $sql = preg_replace("/'[^']*'/", '?', $sql);

        return preg_replace('/\s+/', ' ', trim($sql));
    }

    /**
     * Transactions, savepoints and the like are issued per test by the harness
     * itself and say nothing about the code under test.
     */
    private static function isWorthCounting(string $sql): bool
    {
        return (bool) preg_match('/^\s*(select|insert|update|delete)\b/i', $sql);
    }

    /**
     * @param array<array{sql: string, count: int, distinctBindings: int}> $repeats
     */
    public static function describe(array $repeats): string
    {
        $lines = [];

        foreach ($repeats as $repeat) {
            $lines[] = sprintf(
                '  %dx (%d distinct bindings): %s',
                $repeat['count'],
                $repeat['distinctBindings'],
                strlen($repeat['sql']) > 160 ? substr($repeat['sql'], 0, 160).'…' : $repeat['sql']
            );
        }

        return implode("\n", $lines);
    }
}
