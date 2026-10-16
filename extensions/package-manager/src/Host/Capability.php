<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

use Flarum\ExtensionManager\Lang;
/**
 * What this host will and will not let the Extension Manager do, established by looking
 * rather than by hoping.
 *
 * 🚨 This is shown to the admin BEFORE they press anything, and that is the
 * point of it. The single most infuriating thing about the current tooling is
 * that it behaves mysteriously on constrained hosting and never says why — you
 * discover the limit by hitting it, halfway through an update, with the site
 * down. A tool that knows it cannot do zero-downtime updates should say so on a
 * settings page, in advance.
 *
 * The thresholds are measured, not guessed. On a 253-package install a full
 * resolve peaks at 162 MB and a targeted one at 123 MB, so:
 *
 *   under 160 MB — cannot resolve at all
 *   160–192 MB   — targeted updates only
 *   192 MB and up — everything
 */
class Capability
{
    public const FULL = 'full';
    public const TARGETED = 'targeted';
    public const NONE = 'none';

    /**
     * 🚨 MEASURED, in-process, on dev.ernestdefoe.online's own composer.json
     * with proc_open disabled — a cold Composer cache, then a warm one. Shown
     * on the panel so a short time limit can be judged against a real number.
     */
    private const RESOLVE_COLD = '145';
    private const RESOLVE_WARM = '27';

    private PhpBinary $php;

    /** @var array{memory:bool, time:bool, timeLimit:int}|null */
    private ?array $limits = null;

    /** @var list<array{url:string, why:string}>|null */
    private ?array $blockers = null;

    /**
     * @param array{memory:bool, time:bool, timeLimit:int}|null $limits for tests: what the host lets be lifted
     */
    public function __construct(
        private string $installPath,
        PhpBinary $php,
        private ?string $composerHome = null,
        ?array $limits = null,
    ) {
        $this->php = $php;
        $this->limits = $limits;
    }

    /**
     * Composer will run inside the web request: no separate process can be
     * started, or there is no command-line PHP to start one with.
     */
    public function inProcess(): bool
    {
        return ! $this->canSpawn() || $this->php->path() === null;
    }

    /** @return array{memory:bool, time:bool, timeLimit:int} */
    private function limits(): array
    {
        return $this->limits ??= \Flarum\ExtensionManager\Work\InProcess::limits();
    }

    /** @return array<string,mixed> */
    public function report(): array
    {
        $memory = $this->memoryBytes();
        $checks = [
            $this->memoryCheck($memory),
            $this->timeCheck(),
            $this->subprocessCheck(),
            ...$this->phpCheck(),
            ...$this->gitCheck(),
            $this->opcacheCheck(),
            $this->diskCheck(),
        ];

        return [
            'resolves' => $this->resolveTier($memory),
            'tier' => $this->applyTier(),
            'checks' => $checks,
            'summary' => $this->summary($memory),
            'summaryKey' => $this->summaryKey(),
        ];
    }

    /** How much of an update this host can plan for itself. */
    public function resolveTier(?int $bytes = null): string
    {
        $bytes ??= $this->memoryBytes();

        if ($bytes === -1) {
            return self::FULL;                      // no limit at all
        }

        $mb = $bytes / 1048576;

        return match (true) {
            $mb >= 192 => self::FULL,
            $mb >= 160 => self::TARGETED,
            default => self::NONE,
        };
    }

    /**
     * How updates are applied here.
     *
     * 🚨 One strategy, on every host, and that is a decision rather than a
     * missing feature. This used to advertise a second "slots" tier where a
     * symlink is flipped between prepared directories so nothing is ever
     * missing — the panel said so, and no such code existed.
     *
     * It was not built because it is wrong. A symlink flip is atomic on disk
     * and invisible to PHP: with `opcache.revalidate_path=0` — the default,
     * measured as false on the machine this was written on — opcache resolves a
     * symlink once and caches the result, and realpath_cache_ttl holds the old
     * target for a further two minutes. Slots would trade a microsecond where a
     * package is missing for minutes of quietly serving the old code, which is
     * far worse: nothing looks wrong.
     *
     * Replacing a directory keeps the path constant, so the ordinary timestamp
     * check picks it up. See Opcache for the case where that check is off.
     */
    public function applyTier(): string
    {
        return 'journal';
    }

    private function memoryCheck(int $bytes): array
    {
        if ($this->inProcess() && $this->limits()['memory']) {
            return [
                'id' => 'memory',
                'ok' => true,
                'warn' => false,
                'whatKey' => 'host.memory_in_process',
                'whatParams' => ['limit' => (string) ini_get('memory_limit')],
                'whyKeys' => [['key' => 'host.memory_in_process_why']],
            ];
        }

        $tier = $this->resolveTier($bytes);
        $mb = $bytes === -1 ? Lang::trans('capability.unlimited') : round($bytes / 1048576).' MB';

        return [
            'id' => 'memory',
            'ok' => $tier !== self::NONE,
            'warn' => $tier === self::TARGETED,
            'what' => Lang::trans('capability.memory', ['memory' => $mb]),
            'why' => match ($tier) {
                self::FULL => Lang::trans('capability.memory_full'),
                self::TARGETED => Lang::trans('capability.memory_targeted'),
                default => Lang::trans('capability.memory_none'),
            },
        ];
    }

    private function timeCheck(): array
    {
        $limit = (int) ini_get('max_execution_time');

        /*
         * 🚨 In-process, the resolve is ONE long step inside one request, so
         * the per-request limit is suddenly the thing that matters — and on
         * IONOS/Plesk shared hosting it is both short and locked. Said here,
         * before anybody presses Update, with the number.
         */
        if ($this->inProcess()) {
            $lifted = $limit === 0 || $this->limits()['time'];

            return [
                'id' => 'time',
                'ok' => true,
                'warn' => ! $lifted,
                'whatKey' => $limit === 0 ? 'host.time_none' : 'host.time_limit',
                'whatParams' => ['seconds' => (string) $limit],
                'whyKeys' => $lifted
                    ? [['key' => 'host.time_in_process_lifted']]
                    : [
                        ['key' => 'host.time_in_process_locked', 'params' => ['seconds' => (string) $limit]],
                        ['key' => 'host.time_in_process_retry'],
                    ],
            ];
        }

        return [
            'id' => 'time',
            'ok' => true,
            'warn' => false,
            'what' => $limit === 0 ? Lang::trans('capability.time_none') : Lang::trans('capability.time_limit', ['seconds' => $limit]),
            'why' => $limit === 0
                ? Lang::trans('capability.time_none_why')
                : Lang::trans('capability.time_limit_why', ['seconds' => $limit]),
        ];
    }

    private function subprocessCheck(): array
    {
        /*
         * 🚨 It names the php.ini, and says that it is the WEBSITE's. The
         * usual way somebody "proves" this is wrong is to test proc_open over
         * SSH, where it works — because that is a different PHP with its own
         * configuration.
         */
        if (! $this->inProcess()) {
            return [
                'id' => 'subprocess',
                'ok' => true,
                'warn' => false,
                'whatKey' => 'host.spawn_ok',
                'whyKeys' => [['key' => 'host.spawn_ok_why']],
            ];
        }

        /*
         * Not a blocker any more: Composer runs inside the request instead.
         * A warning, because that shares the request's memory and time — and
         * the fix that removes the warning is named.
         */
        $ini = $this->php->diagnose()['ini'];
        $lines = [
            ['key' => 'host.in_process_why'],
            ['key' => 'host.in_process_limits', 'params' => ['cold' => self::RESOLVE_COLD, 'warm' => self::RESOLVE_WARM]],
        ];

        if (! $this->canSpawn()) {
            $lines[] = $ini !== null
                ? ['key' => 'host.in_process_fix_ini', 'params' => ['ini' => $ini]]
                : ['key' => 'host.in_process_fix'];
            $lines[] = ['key' => 'host.ssh_differs'];
        } else {
            $lines[] = ['key' => 'host.in_process_no_php'];
        }

        return [
            'id' => 'subprocess',
            'ok' => true,
            'warn' => true,
            'whatKey' => 'host.in_process',
            'whyKeys' => $lines,
        ];
    }

    /**
     * Package sources Composer could only read with git — impossible in-process.
     *
     * @return list<array<string,mixed>>
     */
    private function gitCheck(): array
    {
        if (! $this->inProcess()) {
            return [];
        }

        $blockers = $this->blockers ??= (new \Flarum\ExtensionManager\Work\NeedsGit(
            $this->installPath,
            $this->composerHome ?? $this->installPath.'/storage/.composer'
        ))->blockers();

        if ($blockers === []) {
            return [];
        }

        $tokens = array_column(array_filter($blockers, fn ($b) => $b['why'] === 'token'), 'url');
        $git = array_column(array_filter($blockers, fn ($b) => $b['why'] === 'git'), 'url');

        return [[
            'id' => 'git',
            'ok' => false,
            'warn' => false,
            'whatKey' => 'host.git_needed',
            'whyKeys' => array_values(array_filter([
                ['key' => 'host.git_needed_why'],
                $tokens !== [] ? ['key' => 'host.git_needed_token', 'params' => ['urls' => implode(', ', $tokens)]] : null,
                $git !== [] ? ['key' => 'host.git_needed_git', 'params' => ['urls' => implode(', ', $git)]] : null,
            ])),
        ]];
    }

    /**
     * Is there a command-line PHP to run Composer with — and if not, exactly
     * what is in the way.
     *
     * Three different faults that used to share one sentence: proc_open is
     * disabled (the row above), proc_open works but no CLI PHP was found
     * (open_basedir, or not installed), or one was found and fails to run.
     *
     * @return list<array<string,mixed>>
     */
    private function phpCheck(): array
    {
        if (! $this->canSpawn()) {
            return [];     // the row above already says why nothing can run
        }

        $d = $this->php->diagnose();

        if ($d['found'] !== null) {
            $lines = [['key' => $d['override'] !== null ? 'host.php_found_override_why' : 'host.php_found_why']];

            return [[
                'id' => 'php',
                'ok' => true,
                'warn' => false,
                'whatKey' => 'host.php_found',
                'whatParams' => ['path' => $d['found'], 'version' => (string) ($d['version'] ?? '')],
                'whyKeys' => $lines,
                'override' => $d['override'],
            ]];
        }

        $lines = [];

        if ($d['override'] !== null) {
            $lines[] = ['key' => 'host.php_override_broken', 'params' => ['path' => $d['override']]];
        }

        if ($d['detected'] !== null) {
            $what = ['host.php_override_broken_title', []];
            $lines[] = ['key' => 'host.php_override_detected', 'params' => ['path' => $d['detected']]];
        } elseif ($d['failedPath'] !== null) {
            $what = ['host.php_fails', ['path' => $d['failedPath']]];
            $lines[] = ['key' => 'host.php_fails_why', 'params' => ['error' => (string) $d['failedError']]];
        } elseif ($d['openBasedir'] !== null && $d['hiddenPath'] !== null) {
            $what = ['host.php_hidden', ['path' => $d['hiddenPath']]];
            $dir = dirname($d['hiddenPath']);
            $lines[] = ['key' => 'host.php_hidden_why', 'params' => ['openBasedir' => $d['openBasedir']]];
            $lines[] = ['key' => match ($d['panel']) {
                PhpBinary::PANEL_PLESK => 'host.php_hidden_fix_plesk',
                PhpBinary::PANEL_CPANEL => 'host.php_hidden_fix_cpanel',
                default => 'host.php_hidden_fix_generic',
            }, 'params' => ['dir' => $dir.'/']];
        } else {
            $what = ['host.php_missing', []];
            $lines[] = ['key' => 'host.php_missing_why'];
        }

        $lines[] = ['key' => 'host.ssh_differs'];
        $lines[] = ['key' => 'host.php_override_hint'];

        // Not a blocker: without it, Composer and Flarum's commands run inside
        // the web request instead (the row above says what that means).
        return [[
            'id' => 'php',
            'ok' => true,
            'warn' => true,
            'whatKey' => $what[0],
            'whatParams' => $what[1],
            'whyKeys' => $lines,
            'override' => $d['override'],
        ]];
    }

    /**
     * 🚨 The check that decides whether an update is VISIBLE.
     *
     * Everything else here is about whether an update can run. This is about
     * whether anybody will be able to tell that it did — on a host tuned with
     * validate_timestamps off, new files land, every phase reports success, and
     * the site serves the old code until PHP is restarted.
     */
    private function opcacheCheck(): array
    {
        $o = (new Opcache())->situation();

        if (! $o['enabled']) {
            return [
                'id' => 'opcache', 'ok' => true, 'warn' => false,
                'what' => Lang::trans('capability.opcache_off'),
                'why' => Lang::trans('capability.opcache_off_why'),
            ];
        }

        /*
         * 🚨 Not a warning when the Extension Manager can handle it, and it usually can:
         * the finalise step clears the cache from the web request that runs it.
         * The row exists so somebody debugging "the update did not take" has
         * somewhere to look, not to alarm them about a normal setup.
         */
        $handled = $o['canReset'];

        return [
            'id' => 'opcache',
            'ok' => $handled,
            'warn' => ! $handled,
            'what' => $o['validates']
                ? Lang::trans('capability.opcache_rereads', ['seconds' => max(1, $o['freq'])])
                : Lang::trans('capability.opcache_never_rereads'),
            'why' => $handled
                ? Lang::trans('capability.opcache_handled')
                : ($o['validates']
                    ? Lang::trans('capability.opcache_delay', ['seconds' => max(1, $o['freq'])])
                    : Lang::trans('capability.opcache_restart')),
        ];
    }

    private function diskCheck(): array
    {
        $free = @disk_free_space($this->installPath);
        $gb = $free === false ? null : round($free / 1073741824, 1);

        return [
            'id' => 'disk',
            'ok' => $gb === null || $gb > 1,
            'warn' => $gb !== null && $gb <= 1,
            'what' => $gb === null ? Lang::trans('capability.disk_unknown') : Lang::trans('capability.disk_free', ['gb' => $gb]),
            'why' => Lang::trans('capability.disk_why'),
        ];
    }

    private function summary(int $bytes): string
    {
        return match ($this->resolveTier($bytes)) {
            self::FULL => Lang::trans('capability.summary_full'),
            self::TARGETED => Lang::trans('capability.summary_targeted'),
            default => Lang::trans('capability.summary_none'),
        };
    }

    /** The summary when Composer runs in-process, which the English one would call "everything works". */
    private function summaryKey(): ?string
    {
        if (! $this->inProcess() || $this->resolveTier() === self::NONE) {
            return null;
        }

        if ($this->gitCheck() !== []) {
            return 'host.summary_in_process_git';
        }

        return $this->limits()['time'] || (int) ini_get('max_execution_time') === 0
            ? 'host.summary_in_process'
            : 'host.summary_in_process_time';
    }

    private function memoryBytes(): int
    {
        // In-process, the Extension Manager lifts the limit for the resolve if it may.
        if ($this->inProcess() && $this->limits()['memory']) {
            return -1;
        }

        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || $raw === '-1') {
            return -1;
        }

        $unit = strtolower(substr($raw, -1));
        $n = (int) $raw;

        return match ($unit) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    private function canSpawn(): bool
    {
        return $this->php->canSpawn();
    }
}
