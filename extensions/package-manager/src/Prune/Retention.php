<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Prune;

/**
 * How long rollback copies are kept, and when they were last tidied.
 *
 * 🚨 A file under storage/extension-manager, like everything else the Extension Manager keeps,
 * rather than a Flarum setting. The prune runs at the end of an update — inside
 * a queue worker, mid-way through a run whose whole design is that it needs
 * nothing but the disk — and it must read the same answer there as the admin
 * screen does.
 */
class Retention
{
    public const DEFAULT_DAYS = 30;
    public const DEFAULT_RUNS = 5;

    public function __construct(private string $dir)
    {
    }

    /** @return array{keepDays:int, keepRuns:int} */
    public function settings(): array
    {
        $data = $this->read('retention.json');

        return [
            'keepDays' => $this->clamp($data['keepDays'] ?? self::DEFAULT_DAYS, 3650, self::DEFAULT_DAYS),
            'keepRuns' => $this->clamp($data['keepRuns'] ?? self::DEFAULT_RUNS, 1000, self::DEFAULT_RUNS),
        ];
    }

    public function save(mixed $keepDays, mixed $keepRuns): void
    {
        $this->write('retention.json', [
            'keepDays' => $this->clamp($keepDays, 3650, self::DEFAULT_DAYS),
            'keepRuns' => $this->clamp($keepRuns, 1000, self::DEFAULT_RUNS),
        ]);
    }

    /** @return array<string,mixed>|null */
    public function lastPrune(): ?array
    {
        $data = $this->read('pruned.json');

        return $data === [] ? null : $data;
    }

    /** @param array<string,mixed> $summary */
    public function recordPrune(array $summary): void
    {
        $this->write('pruned.json', $summary);
    }

    /**
     * 🚨 Zero is allowed and means "only what the newest update needs" — the
     * prune keeps that whatever these say. A value that is not a whole number
     * falls back to the default rather than to zero, so a typo can never be
     * read as "keep nothing".
     */
    private function clamp(mixed $value, int $max, int $default): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            return $default;
        }

        return max(0, min($max, (int) $value));
    }

    /** @return array<string,mixed> */
    private function read(string $file): array
    {
        $path = $this->dir.'/'.$file;
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($data) ? $data : [];
    }

    /** @param array<string,mixed> $data */
    private function write(string $file, array $data): void
    {
        if (! is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }

        $path = $this->dir.'/'.$file;
        $temp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        file_put_contents($temp, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        if (! @rename($temp, $path)) {
            @unlink($temp);
        }
    }
}
