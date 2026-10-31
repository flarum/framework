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
     * A pointer, appended to the first warning of a run, explaining how to see
     * the detail.
     *
     * PHPUnit only prints warning messages when the phpunit config sets
     * `displayDetailsOnTestsThatTriggerWarnings`; without it the run reports a
     * bare count. Since that is the default in most extensions, the first
     * warning has to carry its own instructions — and only the first, or it
     * repeats on every finding.
     *
     * Tests usually run with `processIsolation`, so "first" cannot be tracked
     * in memory: each test is a fresh process. A marker file next to the
     * findings log gives one hint per run instead of one per process.
     */
    public static function hintOnce(): string
    {
        $hint = "\n\nRun with --display-warnings, or set"
            ."\ndisplayDetailsOnTestsThatTriggerWarnings=\"true\" in your phpunit config, to see"
            ."\nwhich queries these were.";

        // Keyed to the project and to the hour, so every isolated test process
        // in a run agrees on what "first" means without needing cleanup that a
        // per-test process can't reliably perform. A later run gets a fresh
        // hint; a run straddling the hour boundary may hint twice, which is a
        // fair trade for not carrying state between processes.
        $marker = sprintf(
            '%s/flarum-repeated-queries-hinted-%s',
            sys_get_temp_dir(),
            md5((string) realpath('.').gmdate('YmdH'))
        );

        // `x` mode succeeds only for whoever gets there first.
        $handle = @fopen($marker, 'x');

        if ($handle === false) {
            return '';
        }

        fclose($handle);

        return $hint;
    }

    /**
     * Record a finding for tooling to pick up after the run.
     *
     * PHPUnit's own warnings are per-test and easy to miss in a long log, so
     * findings are also appended to a file when FLARUM_REPEATED_QUERY_LOG
     * points at one. CI turns that into annotations and a run summary, which
     * is what reaches a developer who only ever looks at the pull request.
     *
     * @param array<array{sql: string, count: int, distinctBindings: int}> $repeats
     */
    public static function log(string $where, array $repeats, bool $scaling): void
    {
        $path = getenv('FLARUM_REPEATED_QUERY_LOG');

        if (! $path) {
            return;
        }

        foreach ($repeats as $repeat) {
            file_put_contents($path, json_encode([
                'where' => $where,
                'scaling' => $scaling,
                'count' => $repeat['count'],
                'distinctBindings' => $repeat['distinctBindings'],
                'sql' => $repeat['sql'],
            ])."\n", FILE_APPEND | LOCK_EX);
        }
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
