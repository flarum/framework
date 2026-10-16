<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;
use Throwable;

/**
 * Which database migrations an update ran, so undoing it can reverse them.
 *
 * 🚨 Undo used to put the old CODE back over the new SCHEMA. Moving files is
 * all a rename can do, and a migration the update ran stays run: the older
 * version then boots against columns and tables it has never heard of, or
 * misses ones it needs. ClaudiusH asked the obvious question on 2026-10-07 —
 * "this should only be offered if the update did not include a migration,
 * right?" — and the honest answer was that the confirm box merely warned.
 *
 * So the update writes down what it ran, and undo reverses exactly those,
 * newest first, through each migration's own `down`. If any of them has no
 * down step, undo refuses before touching anything: old code on a newer
 * database is the outcome this exists to prevent.
 *
 * 🚨 In TWO requests, and from COPIES. Found undoing Flarum rc.8 → nightly,
 * 2026-10-07: nightly's API middleware reads the `asset_revisions` table its
 * own migration creates, on every response. Reversing that migration inside
 * the undo request — which still has nightly's code loaded — dropped the
 * table out from under the response, and the undo answered 500 halfway. So
 * the first request only checks, puts the files back and restores the
 * version Flarum records; the reversal runs in the NEXT request, booted from
 * the restored code. That code no longer contains the new migration files,
 * which is why the update keeps a copy of each one it ran.
 *
 * Recorded in two halves so a run killed mid-step still knows: the "before"
 * state is saved once, before migrate runs, and the difference is taken after.
 */
final class MigrationLedger
{
    public function __construct(private string $workDir)
    {
    }

    /** Before `flarum migrate`: what has already run, and the version Flarum records. Saved once. */
    public function before(ConnectionInterface $db): void
    {
        $path = $this->workDir.'/migrations.before.json';

        if (! is_file($path)) {
            file_put_contents($path, json_encode([
                'migrations' => $this->current($db),
                'version' => $db->table('settings')->where('key', 'version')->value('value'),
            ]));
        }
    }

    /**
     * After it: what this update added, in the order it ran, with a copy of
     * each migration file so undo can still reach its down step later.
     */
    public function after(ConnectionInterface $db, ExtensionManager $extensions, string $vendorPath): array
    {
        $before = $this->beforeState();
        $seen = array_flip(array_map(fn ($m) => $m['extension'].'|'.$m['migration'], $before['migrations']));
        $added = array_values(array_filter(
            $this->current($db),
            fn ($m) => ! isset($seen[$m['extension'].'|'.$m['migration']])
        ));
        // Flarum runs them in filename order, which starts with a timestamp.
        usort($added, fn ($a, $b) => strcmp($a['migration'], $b['migration']));

        foreach ($added as $m) {
            $from = $this->liveDir($m['extension'], $extensions, $vendorPath).'/'.$m['migration'].'.php';
            $to = $this->copyDir($m['extension']).'/'.$m['migration'].'.php';
            @mkdir(dirname($to), 0775, true);
            @copy($from, $to);
        }

        file_put_contents($this->workDir.'/migrations.json', json_encode($added, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $added;
    }

    /** @return list<array{migration:string, extension:?string}> what this update ran */
    public function ran(): array
    {
        return array_values((array) json_decode((string) @file_get_contents($this->workDir.'/migrations.json'), true));
    }

    /**
     * First request: refuse, changing nothing, unless every migration this
     * update ran can be reversed. Then put back the version Flarum records —
     * without it the restored code finds a database claiming to be newer and
     * shows "Update Flarum" (503) on every page — and leave the reversal
     * itself for the next request.
     *
     * @return bool whether a reversal is now pending
     */
    public function prepare(ConnectionInterface $db, Migrator $migrator, ExtensionManager $extensions, string $vendorPath): bool
    {
        if ($this->ran() === []) {
            $this->restoreVersion($db);

            return false;
        }

        $this->downs($migrator, $extensions, $vendorPath);   // throws if any cannot be reversed
        $this->restoreVersion($db);
        touch($this->workDir.'/migrations.pending');

        return true;
    }

    public function pending(): bool
    {
        return is_file($this->workDir.'/migrations.pending');
    }

    /**
     * Second request, booted from the restored code: reverse what the update
     * ran, newest first.
     *
     * @return list<string> one line per migration reversed
     */
    public function finish(ConnectionInterface $db, Migrator $migrator, ExtensionManager $extensions, string $vendorPath): array
    {
        if (! $this->pending()) {
            return [];
        }

        $done = [];

        foreach ($this->downs($migrator, $extensions, $vendorPath) as [$m, $down]) {
            try {
                call_user_func($down, $db->getSchemaBuilder());
            } catch (Throwable $e) {
                throw new RuntimeException(
                    Lang::trans('migrations.undo_stopped', ['migration' => $m['migration'], 'error' => $e->getMessage()]).' '
                    .($done ? Lang::trans('migrations.already_reversed', ['list' => implode(', ', $done)]) : Lang::trans('migrations.none_reversed'))
                );
            }

            $migrator->getRepository()->delete($m['migration'], $m['extension']);
            $done[] = $m['migration'];
        }

        // Reversed once; a second undo of the same run has nothing left to do.
        @unlink($this->workDir.'/migrations.json');
        @unlink($this->workDir.'/migrations.pending');

        return array_map(fn ($name) => Lang::trans('migrations.reversed', ['migration' => $name]), $done);
    }

    /**
     * Each migration's down step, newest first, or a refusal naming the one
     * that has none.
     *
     * @return list<array{0: array{migration:string, extension:?string}, 1: callable}>
     */
    private function downs(Migrator $migrator, ExtensionManager $extensions, string $vendorPath): array
    {
        $steps = [];

        foreach (array_reverse($this->ran()) as $m) {
            $copy = $this->copyDir($m['extension']);
            $dir = is_file($copy.'/'.$m['migration'].'.php') ? $copy : $this->liveDir($m['extension'], $extensions, $vendorPath);
            $migration = $migrator->resolve($dir, $m['migration']);

            if (! isset($migration['down']) || ! is_callable($migration['down'])) {
                throw new RuntimeException(
                    $m['extension']
                        ? Lang::trans('migrations.irreversible_ext', ['migration' => $m['migration'], 'extension' => $m['extension']])
                        : Lang::trans('migrations.irreversible', ['migration' => $m['migration']])
                );
            }

            $steps[] = [$m, $migration['down']];
        }

        return $steps;
    }

    private function restoreVersion(ConnectionInterface $db): void
    {
        $version = $this->beforeState()['version'];

        if (is_string($version) && $version !== '') {
            $db->table('settings')->where('key', 'version')->update(['value' => $version]);
        }
    }

    /** @return array{migrations: list<array{migration:string, extension:?string}>, version: ?string} */
    private function beforeState(): array
    {
        $raw = (array) json_decode((string) @file_get_contents($this->workDir.'/migrations.before.json'), true);

        // A run recorded before the version was kept stored the bare list.
        return array_is_list($raw)
            ? ['migrations' => $raw, 'version' => null]
            : ['migrations' => (array) ($raw['migrations'] ?? []), 'version' => $raw['version'] ?? null];
    }

    private function liveDir(?string $extension, ExtensionManager $extensions, string $vendorPath): string
    {
        return $extension === null
            ? $vendorPath.'/flarum/core/migrations'
            : $extensions->getExtension($extension)?->getPath().'/migrations';
    }

    private function copyDir(?string $extension): string
    {
        return $this->workDir.'/migrations/'.($extension ?? 'core');
    }

    /** @return list<array{migration:string, extension:?string}> */
    private function current(ConnectionInterface $db): array
    {
        return $db->table('migrations')->get(['migration', 'extension'])
            ->map(fn ($row) => ['migration' => (string) $row->migration, 'extension' => $row->extension !== null ? (string) $row->extension : null])
            ->values()->all();
    }
}
