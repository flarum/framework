<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Brings Composer's record of what is installed into line with the lock.
 *
 * 🚨 This exists so that the register step never makes Composer touch a file.
 *
 * Composer decides what to install by comparing composer.lock with
 * vendor/composer/installed.json — its own record — not with the disk. After
 * the staged apply the disk already matches the lock, but the record still
 * names the old versions, so a plain `composer install` re-extracts every
 * package this run changed.
 *
 * That re-extraction is not just slow, it can destroy the site. The Composer
 * The Extension Manager runs is the one in the site's own vendor/, so when an update
 * includes Composer's own dependencies (symfony/finder, symfony/console, the
 * polyfills) Composer deletes the code it is running on, dies halfway with
 * `Class "Symfony\Component\Finder\Finder" not found`, and leaves those
 * packages as empty directories. wowcraft.online went down exactly like that
 * on a 33-package update, 2026-10-02: every page a fatal from the autoloader
 * requiring polyfill-php85/bootstrap.php out of an emptied directory.
 *
 * With the record rewritten from the lock first, `install` finds nothing to do
 * and only regenerates the autoloader, which is the part the step needs.
 */
class InstalledRecord
{
    public function __construct(private string $installPath)
    {
    }

    /** Rewrite installed.json from composer.lock. Returns the number of packages recorded. */
    public function syncFromLock(): int
    {
        $lock = $this->read($this->installPath.'/composer.lock');
        $recordPath = $this->installPath.'/vendor/composer/installed.json';
        $record = is_file($recordPath) ? $this->read($recordPath) : [];

        // Composer 1 wrote a bare list; 2 wraps it. Keep whatever extra keys
        // an existing entry carries, above all `install-path`, which a custom
        // installer can point outside vendor/<name>.
        $existing = [];
        foreach ($record['packages'] ?? (array_is_list($record) ? $record : []) as $entry) {
            if (isset($entry['name'])) {
                $existing[strtolower($entry['name'])] = $entry;
            }
        }

        $packages = [];
        $devNames = [];

        foreach (['packages' => false, 'packages-dev' => true] as $section => $isDev) {
            foreach ($lock[$section] ?? [] as $locked) {
                $name = $locked['name'];
                $old = $existing[strtolower($name)] ?? [];

                $entry = $locked;
                // Composer recomputes this from `version` when it is absent; a
                // stale copy from the old entry would describe the wrong version.
                unset($entry['version_normalized']);
                $entry['installation-source'] = isset($locked['dist']) ? 'dist' : 'source';
                $entry['install-path'] = $old['install-path'] ?? '../'.$name;

                $packages[] = $entry;

                if ($isDev) {
                    $devNames[] = $name;
                }
            }
        }

        $out = [
            'packages' => $packages,
            'dev' => $record['dev'] ?? true,
            'dev-package-names' => $devNames,
        ];

        $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Written beside and renamed over, so a kill mid-write cannot leave
        // Composer a truncated record to misread.
        $tmp = $recordPath.'.extension-manager';
        if ($json === false || file_put_contents($tmp, $json."\n") === false || ! rename($tmp, $recordPath)) {
            @unlink($tmp);

            throw new RuntimeException(Lang::trans('installed.rewrite_failed'));
        }

        return count($packages);
    }

    /**
     * How many operations `composer install --dry-run` output says it would
     * perform. Zero is the only safe answer for the register step.
     */
    public static function plannedOperations(string $dryRunOutput): int
    {
        if (! preg_match('/Package operations: (\d+) installs?, (\d+) updates?, (\d+) removals?/', $dryRunOutput, $m)) {
            return 0;
        }

        return (int) $m[1] + (int) $m[2] + (int) $m[3];
    }

    private function read(string $path): array
    {
        $data = json_decode((string) @file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException(Lang::trans('fs.read_failed', ['path' => $path]));
        }

        return $data;
    }
}
