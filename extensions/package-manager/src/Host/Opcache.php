<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

use Flarum\Locale\TranslatorInterface;

/**
 * Whether the code this update writes will actually be the code that runs.
 *
 * An update can succeed completely and change nothing anybody can see. PHP
 * caches compiled scripts, and on a host tuned with
 * `opcache.validate_timestamps=0` it never looks at the files again — so new
 * files land, every phase reports success, and the site serves the old version
 * until somebody restarts PHP. Silent, total, and indistinguishable from the
 * update not having run.
 *
 * It is also the reason this extension replaces directories in place rather
 * than flipping a symlink between prepared slots, which was the original plan.
 * A symlink flip is atomic on disk and invisible to PHP: with
 * `opcache.revalidate_path=0` — the default, measured as false on the machine
 * this was written on — opcache resolves a symlink ONCE and caches the result,
 * and realpath_cache_ttl (120s by default) holds the old target on top of that.
 * Slots would trade a microsecond where a package is missing for minutes of
 * quietly serving the old code, which is a far worse failure because nothing
 * looks wrong.
 *
 * Replacing a directory keeps the path constant, so the ordinary timestamp
 * check picks the change up within revalidate_freq seconds. The boring approach
 * is the correct one here.
 */
class Opcache
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    /** No compiled-code cache in the way at all. */
    public const FINE = 'fine';

    /**
     * Compiled code is cached, so an update is not live until it is cleared.
     *
     * This covers BOTH configurations, and the second one caught me out on a
     * real site. `validate_timestamps=0` never re-reads files — obvious. But
     * `validate_timestamps=1` with `revalidate_freq=60` — the default in
     * Flarum's own Docker image — does not re-read them for up to a MINUTE
     * either, which is long enough for the site to serve a fatal error for
     * classes that exist on disk.
     *
     * Installing this very extension on a production forum did exactly that:
     * Composer regenerated the autoloader, the extension was enabled a second
     * later, and every page returned 500 with "class not found" until the
     * revalidate window elapsed. Treating a nonzero freq as "fine" would leave
     * that gap open for every update the Extension Manager performs.
     */
    public const STALE_RISK = 'stale-risk';

    /**
     * @return array{state:string, enabled:bool, validates:bool, freq:int, canReset:bool}
     */
    public function situation(): array
    {
        if (! function_exists('opcache_get_configuration')) {
            return ['state' => self::FINE, 'enabled' => false, 'validates' => true, 'freq' => 0, 'canReset' => false];
        }

        $config = @opcache_get_configuration();

        return $this->situationFrom(is_array($config) ? $config['directives'] : []);
    }

    /**
     * The same judgement, from directives handed in.
     *
     * Separated so the rule can be tested against the configurations that
     * actually exist in the wild — including the one that caused a production
     * site to serve fatal errors — rather than only against whatever this
     * machine happens to be set to.
     *
     * @param array<string,mixed> $directives
     * @return array{state:string, enabled:bool, validates:bool, freq:int, canReset:bool}
     */
    public function situationFrom(array $directives): array
    {
        /*
         * `opcache.enable` is per-SAPI, and this must be answered for the SAPI
         * that will SERVE the pages — not for whichever process happens to ask.
         * A CLI worker reports opcache disabled (enable_cli is off by default)
         * while the web pool has it very much enabled, so a check that trusts
         * the asking process would report "fine" from the one place that cannot
         * see the problem.
         */
        $enabled = (bool) ($directives['opcache.enable'] ?? false);
        $validates = (bool) ($directives['opcache.validate_timestamps'] ?? true);

        return [
            /*
             * Any enabled opcache needs clearing, not just one with
             * validate_timestamps off. See the constant above: a 60-second
             * revalidate window is a 60-second window of serving code that no
             * longer matches the files, and the cost of clearing is a recompile
             * that was going to happen anyway.
             */
            'state' => $enabled ? self::STALE_RISK : self::FINE,
            'enabled' => $enabled,
            'validates' => $validates,
            'freq' => (int) ($directives['opcache.revalidate_freq'] ?? 0),
            'canReset' => function_exists('opcache_reset'),
        ];
    }

    /**
     * Throw away the compiled code so the new files are read.
     *
     * Only meaningful in the process pool that serves pages. Calling this
     * from a CLI queue worker resets that worker's own cache, which nobody is
     * using, and leaves the web pool exactly as stale as before — so this
     * reports whether it did anything real rather than returning success for
     * having called a function.
     *
     * @return array{done:bool, why:string}
     */
    public function clear(): array
    {
        $situation = $this->situation();

        if ($situation['state'] === self::FINE) {
            return ['done' => true, 'why' => $this->translator->trans('flarum-extension-manager.api.opcache.fine')];
        }

        if (PHP_SAPI === 'cli') {
            /*
             * The queue-worker case. Said plainly rather than papered over: the
             * files are correct and the site is serving stale compiled copies,
             * which is exactly the sort of thing that gets diagnosed three days
             * later as "the update didn't work".
             */
            return [
                'done' => false,
                'why' => $situation['validates']
                    ? $this->translator->trans('flarum-extension-manager.api.opcache.cli_delay', ['seconds' => max(1, $situation['freq'])])
                    : $this->translator->trans('flarum-extension-manager.api.opcache.cli_restart'),
            ];
        }

        if (! $situation['canReset'] || ! @opcache_reset()) {
            /*
             * false does not always mean "could not". opcache_reset()
             * answers false while an earlier reset is still PENDING — it waits
             * for every worker to go idle — and a pending reset has already
             * switched the cache off, so every request compiles from disk.
             * Reading that as a failure parked an in-process run on dev for
             * most of the 60-second revalidate window, twice per update.
             */
            if ($this->resetPending()) {
                return ['done' => true, 'why' => $this->translator->trans('flarum-extension-manager.api.opcache.cleared')];
            }

            return [
                'done' => false,
                'why' => $situation['validates']
                    ? $this->translator->trans('flarum-extension-manager.api.opcache.not_cleared_delay', ['seconds' => max(1, $situation['freq'])])
                    : $this->translator->trans('flarum-extension-manager.api.opcache.not_cleared_restart'),
            ];
        }

        return ['done' => true, 'why' => $this->translator->trans('flarum-extension-manager.api.opcache.cleared')];
    }

    /** A reset has been asked for and not happened yet: the cache is bypassed meanwhile. */
    protected function resetPending(): bool
    {
        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;

        return is_array($status) && ! empty($status['restart_pending']);
    }

    /**
     * The command line cannot clear the web server's compiled code, but it can
     * ask. A flag in storage, honoured by the next web request (see
     * honourWebReset(), called from bootstrap/web-reset.php), resets the cache
     * the whole php-fpm pool shares.
     *
     * Without it, a core update run from the CLI moved the database to 2.0.0
     * while php-fpm kept serving rc.8 for minutes. Every page was Flarum's 503
     * "Update Flarum", and a request in that window compiled the forum CSS from
     * the old code, failed, and switched the custom LESS off (wowcraft,
     * 2026-10-09).
     *
     * Directly in storage/, at a path that never moves, so the web request
     * checks it with one stat and no class loaded. Touching an existing flag
     * moves its time on, which is what asks again.
     */
    public static function requestWebReset(string $storagePath): void
    {
        @touch(self::flag($storagePath));
    }

    public static function webResetPending(string $storagePath): bool
    {
        clearstatcache(true, self::flag($storagePath));

        return is_file(self::flag($storagePath));
    }

    /**
     * On a web request that found the flag: take it (a rename, so one worker
     * acts) and reset.
     *
     * A flag this process cannot take, made by root in a directory the web
     * server cannot write to, must not leave every request resetting the cache
     * for ever. The cache's own last restart is the marker: a flag older than
     * it has been honoured, so the request does nothing more than compare.
     *
     * @param array<string,mixed>|false|null $status opcache_get_status(false)
     */
    public static function honourWebReset(string $storagePath, array|false|null $status = null): void
    {
        $flag = self::flag($storagePath);
        clearstatcache(true, $flag);
        $requested = @filemtime($flag);

        if ($requested === false) {
            return;
        }

        // Handed in only by the tests; otherwise this process's own cache.
        $status ??= function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $lastReset = is_array($status) ? (int) ($status['opcache_statistics']['last_restart_time'] ?? 0) : null;

        if ($lastReset !== null && $lastReset > $requested) {
            return;
        }

        $taken = $flag.'.'.getmypid();

        if (@rename($flag, $taken)) {
            @unlink($taken);
        }

        if ($lastReset !== null && function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    protected static function flag(string $storagePath): string
    {
        return $storagePath.'/extension-manager-opcache-reset';
    }
}
