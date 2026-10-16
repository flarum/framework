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
 * Where the command-line PHP is — and, when it cannot be found, WHY.
 *
 * 🚨 PHP_BINARY is the wrong answer under FPM, and FPM is most hosts. Under
 * php-fpm it is the FPM binary — handing it a script does not run the script,
 * and the failure is the confusing kind: proc_open succeeds, a process starts,
 * and it exits having done nothing recognisable.
 *
 * 🚨 Every candidate is PROVED rather than trusted, by running it and asking
 * which SAPI it is. Guessing from the filename is not good enough: the first
 * version of this trusted PHP_BINDIR, and on the machine it was written on
 * PHP_BINDIR is `/bin` — which contains no PHP at all. It failed safely and
 * with a clear message, which is the only reason it was cheap to find.
 *
 * 🚨 The most reliable candidate is the FPM binary's OWN DIRECTORY with `-fpm`
 * stripped: `php85-fpm` sits beside `php85`, same build, same version, same
 * extensions. That layout is shared by Herd, cPanel, Plesk and most distro
 * packages, and it is checked before anything on a fixed path so a forum gets
 * the PHP it is actually running rather than whatever the host defaults to.
 *
 * 🚨 open_basedir. Plesk sets it on every site by default
 * (`{WEBSPACEROOT}{/}{:}{TMP}{/}`), and under it `is_file('/opt/plesk/php/8.5/bin/php')`
 * is simply FALSE from the website, while the same check over SSH — a different
 * PHP with a different php.ini — is true. A real customer on Plesk was stuck
 * there: the Extension Manager said only that it could not run. open_basedir limits PHP's
 * own file functions, not the programs proc_open starts, so a candidate it
 * hides is still TRIED by running it; and when nothing answers, the diagnosis
 * names open_basedir and the exact path it hid.
 */
class PhpBinary
{
    public const PANEL_PLESK = 'plesk';
    public const PANEL_CPANEL = 'cpanel';

    private ?string $resolved = null;
    private bool $searched = false;

    /** @var array<string, array{ok:bool, hidden:bool, ran:bool, sapi:?string, version:?string, error:?string}> */
    private array $probes = [];

    /**
     * Everything but the first argument is for tests — they stand in for the
     * host this runs on, so open_basedir and a panel's layout can be asserted
     * without being configured.
     *
     * @param list<string>|null $candidates
     * @param (\Closure(string): array{code:int, out:string, err:string})|null $runner
     */
    public function __construct(
        private ?string $override = null,
        private ?array $candidates = null,
        private ?string $openBasedir = null,
        private ?\Closure $runner = null,
        private ?string $phpBinary = null,
        private ?string $sapi = null,
        private ?string $disabledFunctions = null,
    ) {
    }

    /** The PHP this forum's processes run with: the admin's choice, if they made one. */
    public static function forStorage(string $storagePath): self
    {
        return new self((new PhpOverride($storagePath))->get());
    }

    public function path(): ?string
    {
        if ($this->override !== null && $this->override !== '') {
            return $this->override;
        }

        if ($this->searched) {
            return $this->resolved;
        }

        $this->searched = true;

        // Under the CLI, PHP_BINARY is certainly a PHP that runs scripts.
        if ($this->sapi() === 'cli' && is_file($this->phpBinary())) {
            return $this->resolved = $this->phpBinary();
        }

        foreach ($this->candidates() as $candidate) {
            if ($this->probe($candidate)['ok']) {
                return $this->resolved = $candidate;
            }
        }

        return $this->resolved = null;
    }

    /**
     * Run one binary and ask what it is. Used for every candidate, and to
     * validate a path an admin types in.
     *
     * @return array{ok:bool, hidden:bool, ran:bool, sapi:?string, version:?string, error:?string}
     */
    public function probe(string $candidate): array
    {
        if (isset($this->probes[$candidate])) {
            return $this->probes[$candidate];
        }

        $hidden = ! $this->visible($candidate);
        $result = ['ok' => false, 'hidden' => $hidden, 'ran' => false, 'sapi' => null, 'version' => null, 'error' => null];

        // Only where PHP is allowed to look. A path open_basedir hides is run
        // anyway: is_file would say "no" about a file that is there.
        if (! $hidden) {
            if (! is_file($candidate)) {
                return $this->probes[$candidate] = $result;
            }

            if (! is_executable($candidate)) {
                return $this->probes[$candidate] = ['error' => 'not executable'] + $result;
            }
        }

        if (! $this->canSpawn()) {
            return $this->probes[$candidate] = $result;
        }

        $run = $this->run([$candidate, '-r', 'echo PHP_SAPI, " ", PHP_VERSION;']);

        // 127/126: exec could not start it — for a hidden path, that is "not
        // there", not "broken".
        if ($run['code'] === 127 || $run['code'] === 126 || ($run['code'] === -1 && $hidden)) {
            return $this->probes[$candidate] = $result;
        }

        [$sapi, $version] = array_pad(explode(' ', trim($run['out']), 2), 2, null);

        // php-fpm has no -r: it prints its usage, which is not a SAPI name.
        if ($sapi !== null && ! preg_match('/^[a-z][a-z0-9-]*$/', $sapi)) {
            $sapi = str_contains(basename($candidate), 'fpm') ? 'fpm-fcgi' : null;
            $version = null;
        }
        $result['ran'] = true;
        $result['sapi'] = $sapi;
        $result['version'] = $version;
        $result['ok'] = $run['code'] === 0 && $sapi === 'cli';

        if (! $result['ok']) {
            $err = trim($run['err']) !== '' ? trim($run['err']) : trim($run['out']);
            $result['error'] = $err !== ''
                ? mb_substr(strtok($err, "\n") ?: $err, 0, 300)
                : ($sapi !== null && $sapi !== 'cli' ? Lang::trans('php.wrong_sapi', ['sapi' => $sapi]) : Lang::trans('php.exit_code', ['code' => (string) $run['code']]));
        }

        return $this->probes[$candidate] = $result;
    }

    /**
     * What stands between the Extension Manager and a command-line PHP, in the order
     * someone fixing it needs to know.
     *
     * @return array{
     *   spawn: bool, found: ?string, version: ?string, override: ?string,
     *   openBasedir: ?string, hiddenPath: ?string, failedPath: ?string, failedError: ?string,
     *   detected: ?string, panel: ?string, ini: ?string
     * }
     */
    public function diagnose(): array
    {
        $found = $this->path();
        $openBasedir = $this->openBasedir();

        // A path the admin typed is trusted for running, but checked here: it
        // may have been right when saved and gone since a PHP upgrade.
        if ($found !== null && $found === $this->override && ! $this->probe($found)['ok']) {
            $found = null;
        }

        $out = [
            'spawn' => $this->canSpawn(),
            'found' => $found,
            'version' => null,
            'override' => $this->override !== '' ? $this->override : null,
            'openBasedir' => $openBasedir,
            'hiddenPath' => null,
            'failedPath' => null,
            'failedError' => null,
            'detected' => null,
            'panel' => $this->panel(),
            'ini' => $this->iniFile(),
        ];

        if ($found !== null) {
            $out['version'] = $this->probe($found)['version'] ?? null;

            return $out;
        }

        // The candidate most likely to be "the one", hidden or broken. The
        // list is already in order of likelihood.
        foreach ($this->candidates() as $candidate) {
            $p = $this->probe($candidate);

            if ($p['ok']) {
                // The override is broken but auto-detection would work.
                $out['found'] = null;
                $out['detected'] = $candidate;
                break;
            }

            if ($out['failedPath'] === null && $p['ran']) {
                $out['failedPath'] = $candidate;
                $out['failedError'] = $p['error'];
            }

            if ($out['hiddenPath'] === null && $p['hidden'] && $this->looksLikePanelPhp($candidate)) {
                $out['hiddenPath'] = $candidate;
            }
        }

        if ($out['hiddenPath'] === null) {
            foreach ($this->candidates() as $candidate) {
                if ($this->probe($candidate)['hidden']) {
                    $out['hiddenPath'] = $candidate;
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Which control panel runs this site, from where its own PHP lives — the
     * one thing open_basedir cannot hide from us.
     */
    public function panel(): ?string
    {
        if (str_starts_with($this->phpBinary(), '/opt/plesk/')) {
            return self::PANEL_PLESK;
        }

        if (str_starts_with($this->phpBinary(), '/opt/cpanel/') || str_starts_with($this->phpBinary(), '/usr/local/cpanel/')) {
            return self::PANEL_CPANEL;
        }

        if ($this->visible('/usr/local/psa') && @is_dir('/usr/local/psa')) {
            return self::PANEL_PLESK;
        }

        if ($this->visible('/usr/local/cpanel') && @is_dir('/usr/local/cpanel')) {
            return self::PANEL_CPANEL;
        }

        return null;
    }

    public function canSpawn(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', $this->disabledFunctions ?? (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $disabled, true);
    }

    /** The open_basedir list, or null when there is none. */
    public function openBasedir(): ?string
    {
        $value = $this->openBasedir ?? (string) ini_get('open_basedir');

        return trim($value) === '' ? null : $value;
    }

    /** Is this path inside open_basedir — that is, can PHP's file functions see it? */
    public function visible(string $path): bool
    {
        $list = $this->openBasedir();

        if ($list === null) {
            return true;
        }

        foreach (explode(PATH_SEPARATOR, $list) as $base) {
            $base = trim($base);

            if ($base === '') {
                continue;
            }

            if ($base === '.') {
                $base = (string) getcwd();
            }

            // open_basedir is a PREFIX match: "/var/www" also allows
            // "/var/www2". A trailing slash makes it a directory.
            if (str_starts_with($path, $base)) {
                return true;
            }
        }

        return false;
    }

    /**
     * In order of how likely each is to be the same PHP this request runs under.
     *
     * @return list<string>
     */
    public function candidates(): array
    {
        if ($this->candidates !== null) {
            return $this->candidates;
        }

        $binary = $this->phpBinary();
        $dir = dirname($binary);
        $me = basename($binary);
        $ver = PHP_MAJOR_VERSION.PHP_MINOR_VERSION;
        $dot = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $candidates = [];

        // php85-fpm → php85, right beside it. The same build, so the same
        // extensions and the same php.ini as the request asking.
        if (str_ends_with($me, '-fpm')) {
            $candidates[] = $dir.'/'.substr($me, 0, -4);
        }

        // Plesk: /opt/plesk/php/8.5/sbin/php-fpm → /opt/plesk/php/8.5/bin/php.
        if (basename($dir) === 'sbin') {
            $candidates[] = dirname($dir).'/bin/'.preg_replace('/-fpm$/', '', $me);
        }

        // Plesk and cPanel keep each version in its own tree, and their `php`
        // on the PATH is a selector that may point at a different version than
        // this forum runs on — so their own trees come before /usr/bin.
        $candidates[] = '/opt/plesk/php/'.$dot.'/bin/php';
        $candidates[] = '/opt/cpanel/ea-php'.$ver.'/root/usr/bin/php';

        foreach ([$dir, PHP_BINDIR, '/usr/local/bin', '/usr/bin'] as $where) {
            $candidates[] = $where.'/php'.$ver;
            $candidates[] = $where.'/php'.$dot;
            $candidates[] = $where.'/php';
        }

        return $this->candidates = array_values(array_unique($candidates));
    }

    private function looksLikePanelPhp(string $path): bool
    {
        return str_starts_with($path, '/opt/plesk/php/')
            || str_starts_with($path, '/opt/cpanel/')
            || str_starts_with($path, dirname($this->phpBinary()).'/');
    }

    private function iniFile(): ?string
    {
        $file = php_ini_loaded_file();

        return $file === false ? null : $file;
    }

    private function phpBinary(): string
    {
        return $this->phpBinary ?? PHP_BINARY;
    }

    private function sapi(): string
    {
        return $this->sapi ?? PHP_SAPI;
    }

    /**
     * @param list<string> $command
     * @return array{code:int, out:string, err:string}
     */
    private function run(array $command): array
    {
        if ($this->runner !== null) {
            return ($this->runner)($command[0]);
        }

        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (! is_resource($process)) {
            return ['code' => -1, 'out' => '', 'err' => ''];
        }

        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['code' => proc_close($process), 'out' => $out, 'err' => $err];
    }
}
