<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

/**
 * What changed on this forum, and when: the list an app store shows as
 * "recently updated" (ClaudiusH's idea, 2026-10-07).
 *
 * Its own small file rather than read from the run records, because the
 * pruner removes run folders once no undo can use them — and "what changed
 * last month" is exactly the question people ask after that. One entry per
 * run, written when the run reaches an end (done, failed, undone); an undo
 * updates its run's entry instead of adding a second. The newest LIMIT kept.
 */
final class History
{
    private const LIMIT = 50;

    public function __construct(private string $dataDir)
    {
    }

    public function record(Run $run): void
    {
        if (! $run->isFinished()) {
            return;
        }

        $runDir = $this->dataDir.'/runs/'.$run->id;
        $plan = (array) json_decode((string) @file_get_contents($runDir.'/plan.json'), true);
        $requested = (array) json_decode((string) @file_get_contents($runDir.'/requested.json'), true);
        $migrations = (array) json_decode((string) @file_get_contents($runDir.'/migrations.json'), true);

        $entries = array_values(array_filter($this->all(), fn ($e) => ($e['id'] ?? null) !== $run->id));
        $previous = array_values(array_filter($this->all(), fn ($e) => ($e['id'] ?? null) === $run->id))[0] ?? [];

        array_unshift($entries, [
            'id' => $run->id,
            'at' => $run->movedAt,
            'state' => $run->state,
            'mode' => (string) ($requested['mode'] ?? 'update'),
            // An undo loses nothing: what the update changed is kept from before.
            'changes' => (array) ($plan['changes'] ?? ($previous['changes'] ?? [])),
            'requested' => array_values(array_filter((array) ($requested['packages'] ?? ($previous['requested'] ?? [])), 'is_string')),
            'migrations' => count($migrations) ?: (int) ($previous['migrations'] ?? 0),
        ]);

        $path = $this->path();
        $tmp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        @mkdir(dirname($path), 0775, true);

        if (@file_put_contents($tmp, json_encode(array_slice($entries, 0, self::LIMIT), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
            @rename($tmp, $path) || @unlink($tmp);
        }
    }

    /** @return list<array<string,mixed>> newest first */
    public function all(): array
    {
        return array_values((array) json_decode((string) @file_get_contents($this->path()), true));
    }

    private function path(): string
    {
        return $this->dataDir.'/history.json';
    }
}
