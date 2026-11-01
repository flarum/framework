<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * Every AI call's token usage, one row per call, in `ai_usage`. Only the
 * numbers and what they were for: never the prompt or the reply.
 *
 * @internal
 */
class Ledger
{
    /**
     * How long rows are kept. Limits look back a month at most.
     */
    public const KEEP_DAYS = 90;

    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    public function record(
        ?Context $context,
        string $connection,
        string $driver,
        ?string $model,
        string $kind,
        int $inputTokens,
        int $outputTokens
    ): void {
        $this->table()->insert([
            'feature' => $context?->feature,
            'actor' => $context?->actorKind(),
            'user_id' => $context?->userId(),
            'connection' => mb_substr($connection, 0, 100),
            'driver' => mb_substr($driver, 0, 50),
            'model' => $model === null ? null : mb_substr($model, 0, 100),
            'kind' => $kind,
            'input_tokens' => max(0, $inputTokens),
            'output_tokens' => max(0, $outputTokens),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Tokens used since the given time by calls matching the given column.
     */
    public function tokensSince(string $column, string|int $value, CarbonInterface $since): int
    {
        return (int) $this->table()
            ->where($column, $value)
            ->where('created_at', '>=', $since)
            ->sum($this->db->raw('input_tokens + output_tokens'));
    }

    /**
     * Totals for the admin page: this month by connection, feature and member,
     * and the last 30 days day by day.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $tokens = 'SUM(input_tokens + output_tokens)';

        $grouped = fn (string $column) => $this->table()
            ->where('created_at', '>=', $monthStart)
            ->groupBy($column)
            ->selectRaw("$column as name, COUNT(*) as calls, $tokens as tokens")
            ->orderByDesc('tokens')
            ->get()
            ->map(fn (object $row) => ['name' => $row->name, 'calls' => (int) $row->calls, 'tokens' => (int) $row->tokens])
            ->all();

        $members = $this->table()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('user_id')
            ->selectRaw("user_id as id, COUNT(*) as calls, $tokens as tokens")
            ->orderByDesc('tokens')
            ->limit(10)
            ->get();

        $usernames = $this->db->table('users')->whereIn('id', $members->pluck('id'))->pluck('username', 'id');

        $members = $members
            ->map(fn (object $row) => [
                'id' => (int) $row->id,
                'username' => $usernames[$row->id] ?? null,
                'calls' => (int) $row->calls,
                'tokens' => (int) $row->tokens,
            ])
            ->all();

        $days = [];
        $since = Carbon::now()->startOfDay()->subDays(29);

        for ($day = $since->copy(); $day->lte(Carbon::now()); $day->addDay()) {
            $days[$day->toDateString()] = 0;
        }

        // Summed per row in PHP: grouping by date is spelled differently on
        // every database, and thirty days of rows is little enough.
        $this->table()
            ->where('created_at', '>=', $since)
            ->select(['created_at', 'input_tokens', 'output_tokens'])
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$days) {
                foreach ($rows as $row) {
                    $date = Carbon::parse($row->created_at)->toDateString();

                    if (isset($days[$date])) {
                        $days[$date] += $row->input_tokens + $row->output_tokens;
                    }
                }
            });

        return [
            'month' => $monthStart->format('Y-m'),
            'connections' => $grouped('connection'),
            'features' => $grouped('feature'),
            'members' => $members,
            'days' => array_map(fn ($date, $tokens) => ['date' => $date, 'tokens' => $tokens], array_keys($days), $days),
        ];
    }

    public function prune(): int
    {
        return $this->table()->where('created_at', '<', Carbon::now()->subDays(self::KEEP_DAYS))->delete();
    }

    protected function table(): Builder
    {
        return $this->db->table('ai_usage');
    }
}
