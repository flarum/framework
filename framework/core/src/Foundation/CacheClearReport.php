<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

/**
 * What clearing the cache actually did.
 *
 * Kept separate from how it is presented: the console renders it as a table,
 * the API hands it to the admin panel as JSON. Each step records what happened
 * rather than a sentence about it, so the words can come from the translations
 * on whichever side is doing the rendering — a formatted string built here
 * would be English for ever.
 */
class CacheClearReport
{
    /**
     * A cache that was emptied, and how many files went with it.
     *
     * @var list<array{name: string, files: int|null}>
     */
    private array $cleared = [];

    /**
     * @var list<array{
     *     frontend: string,
     *     locale: string|null,
     *     localeName: string|null,
     *     bundle: string,
     *     state: string,
     *     revision: string|null,
     *     previousRevision: string|null,
     *     bytes: int|null,
     *     changed: int|null,
     *     milliseconds: int
     * }>
     */
    private array $rebuilt = [];

    /**
     * @var list<array{step: string, message: string}>
     */
    private array $failures = [];

    public const UNCHANGED = 'unchanged';
    public const REBUILT = 'rebuilt';
    public const REWRITTEN = 'rewritten';
    public const EMPTY = 'empty';
    public const CHUNKS = 'chunks';

    /**
     * Called with each step as it finishes, for a caller that cannot wait until
     * the end — clearing the cache recompiles every bundle for every frontend
     * and locale, which is tens of seconds where the assets live on remote
     * storage, and an admin watching a spinner has no way to tell that from a
     * request that has died.
     *
     * @var (callable(string, array<string, mixed>): void)|null
     */
    private $listener;

    /**
     * @param (callable(string, array<string, mixed>): void)|null $listener
     */
    public function __construct(?callable $listener = null)
    {
        $this->listener = $listener;
    }

    public function cleared(string $name, ?int $files = null): void
    {
        $this->announce('cleared', $this->cleared[] = compact('name', 'files'));
    }

    /**
     * @param array<string, mixed> $step
     */
    private function announce(string $type, array $step): void
    {
        if ($this->listener !== null) {
            ($this->listener)($type, $step);
        }
    }

    /**
     * One compiled bundle.
     *
     * `$state` says what happened to it — see the constants above — so a
     * renderer can decide whether a revision, a size or neither is worth
     * showing without parsing anything.
     */
    public function rebuilt(
        string $frontend,
        ?string $locale,
        ?string $localeName,
        string $bundle,
        string $state,
        ?string $revision,
        ?string $previousRevision,
        ?int $bytes,
        float $seconds
    ): void {
        $this->announce('rebuilt', $this->rebuilt[] = [
            'frontend' => $frontend,
            'locale' => $locale,
            'localeName' => $localeName,
            'bundle' => $bundle,
            'state' => $state,
            'revision' => $revision,
            'previousRevision' => $previousRevision,
            'bytes' => $bytes,
            'changed' => null,
            'milliseconds' => (int) round($seconds * 1000),
        ]);
    }

    /**
     * The split chunks of one frontend, which have a revision each rather than
     * one between them, so only a count of what moved is meaningful.
     */
    public function rebuiltChunks(string $frontend, int $changed, float $seconds): void
    {
        $this->announce('rebuilt', $this->rebuilt[] = [
            'frontend' => $frontend,
            'locale' => null,
            'localeName' => null,
            'bundle' => 'chunks',
            'state' => static::CHUNKS,
            'revision' => null,
            'previousRevision' => null,
            'bytes' => null,
            'changed' => $changed,
            'milliseconds' => (int) round($seconds * 1000),
        ]);
    }

    /**
     * A step that could not be pre-built. The cache is already cleared by the
     * time any of them run, so these are reported rather than fatal.
     */
    public function failed(string $step, string $message): void
    {
        $this->announce('failed', $this->failures[] = compact('step', 'message'));
    }

    /**
     * @return list<array{name: string, files: int|null}>
     */
    public function getCleared(): array
    {
        return $this->cleared;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRebuilt(): array
    {
        return $this->rebuilt;
    }

    /**
     * @return list<array{step: string, message: string}>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cleared' => $this->cleared,
            'rebuilt' => $this->rebuilt,
            'failures' => $this->failures,
        ];
    }
}
