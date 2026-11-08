<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Config\Repositories;
use Flarum\ExtensionManager\Config\StabilityRule;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Whether anything newer exists, asked cheaply.
 *
 * This is deliberately NOT the same question as "can I have it".
 *
 * Conflating them makes the answer useless: a newer version existing tells you
 * nothing about whether it will install alongside everything else you have. Only a resolve knows that, and a
 * resolve is 16 seconds and 165 MB — far too expensive to run on a page load, or
 * on a schedule, or once per extension.
 *
 * So there are two answers, and they are kept apart on purpose:
 *
 *   this class — "3.6.0 exists and you are on 3.5.0". One cheap HTTP call per
 *                package, cacheable, safe to run nightly. It is a HINT.
 *   the plan   — "here is exactly what changes, what comes with it, and what
 *                blocks it". Run when somebody actually presses Update.
 *
 * Presenting the hint as a promise would be the lie. The badge says a newer
 * version exists; pressing Update is what finds out whether you can have it.
 */
class UpdateCheck
{
    /**
     * Where each version's release notes live, as read from Packagist while
     * checking: package => version => URL. Filled by fromPackagist(), so a
     * check costs no request beyond the one it already makes.
     *
     * @var array<string,array<string,string>>
     */
    protected array $releaseNotes = [];

    /** When the check in progress has to stop asking, if it has a budget. */
    protected ?float $deadline = null;

    public function __construct(
        protected string $cachePath,
        protected ClientInterface $http,
        protected int $freshFor = 21600,        // six hours
    ) {
    }

    /**
     * The packages a composer.json requires itself: names with a vendor, so
     * not php, ext-* or lib-*.
     *
     * @return list<string>
     */
    public static function directRequires(string $composerJson): array
    {
        $data = is_file($composerJson) ? json_decode((string) file_get_contents($composerJson), true) : null;
        $names = array_keys((array) (is_array($data) ? ($data['require'] ?? []) : []));

        return array_values(array_filter($names, fn ($name) => is_string($name) && str_contains($name, '/')));
    }

    /**
     * Only things the site actually chose.
     *
     * Running this against a real forum returned 59 "updates", of which nearly
     * all were transitive dependencies — illuminate/collections 13.30.0 →
     * 13.30.1, guzzle 7 → 8, doctrine 3 → 4. Nobody updates those individually;
     * they arrive with the extension that needs them, and several are pinned by
     * flarum/core so the newer version is not installable at all.
     *
     * Reporting them would be worse than reporting nothing: a badge showing 59
     * when 4 things matter is a badge people stop reading. So the check is
     * limited to Flarum extensions and Flarum itself — the things somebody
     * deliberately installed and might deliberately update.
     *
     * And to anything the forum's own composer.json requires. fof/redis is
     * a Composer library, not an extension, so it has no settings page and
     * was never reported: its stable release went unseen (Ernest,
     * 2026-10-10). Required directly means somebody chose it; pulled in by
     * another package means nobody did, and those stay quiet.
     *
     * @param list<array{name:string,version:string,type?:string}> $packages
     * @param list<string> $direct packages the root composer.json requires
     * @return array<string,string>
     */
    public function interesting(array $packages, array $direct = []): array
    {
        $direct = array_flip($direct);

        $out = [];

        foreach ($packages as $package) {
            $name = (string) ($package['name'] ?? '');
            $type = (string) ($package['type'] ?? '');

            if ($name === '') {
                continue;
            }

            if ($type === 'flarum-extension' || $name === 'flarum/core' || isset($direct[$name])) {
                $out[$name] = (string) ($package['version'] ?? '');
            }
        }

        return $out;
    }

    /**
     * @param array<string,string> $installed package => installed version
     * @param callable(string):(array|false|null) $fetch overridable so this
     *        is testable without a network: the versions, null when no source
     *        knows the package, false when a source could not be asked
     * @param callable(string):array<string,string>|null $notesFor release links
     *        for packages Packagist does not know, version => URL
     * @param StabilityRule|null $rule what the forum lets Composer install;
     *        null offers every release
     * @param int|null $budget seconds the whole check may take; what is left
     *        unasked when they run out keeps what the last check found
     * @return array<string,mixed>
     */
    public function refresh(array $installed, ?callable $fetch = null, ?callable $notesFor = null, ?StabilityRule $rule = null, ?int $budget = null): array
    {
        $fetch ??= fn (string $name) => $this->fromPackagist($name);
        $this->deadline = $budget === null ? null : microtime(true) + $budget;

        /*
         * What the last check knew, for every package this one cannot answer
         * for. A Packagist outage, a timeout or a check cut short used to save
         * `updates: []` with a fresh time, so every badge vanished until the
         * next check. Not being able to ask is not the same as there being
         * nothing newer. Entries for packages no longer installed are not
         * carried over: a stale one made `update --all` refuse the whole run.
         */
        $previous = array_intersect_key((array) ($this->cached()['updates'] ?? []), $installed);

        $result = [
            'checkedAt' => time(),
            'updates' => $previous,
            'uncheckable' => [],
            'tracking' => [],
            'unreachable' => [],
        ];

        foreach ($installed as $name => $version) {
            /*
             * A package tracking a branch is not "uncheckable" — it is not a
             * version question at all.
             *
             * `dev-main` has no version to be newer than. Whether the branch has
             * moved is something only a resolve can answer, and this command
             * deliberately does not resolve. Lumping these in with packages we
             * genuinely could not reach told an admin that four extensions
             * "could not be checked", which is two wrong things at once: they
             * were reachable, and nothing was wrong.
             *
             * It also saves the request, which is the point of a nightly check
             * staying cheap.
             */
            if (self::tracksABranch($version)) {
                unset($result['updates'][$name]);
                $result['tracking'][] = $name;
                continue;
            }

            $versions = $this->outOfTime() ? false : $fetch($name);

            if ($versions === false) {
                // Asked and not answered, or not asked in time: the last
                // check's entry for it stands.
                $result['unreachable'][] = $name;
                continue;
            }

            unset($result['updates'][$name]);

            if ($versions === null) {
                /*
                 * Named, not silently dropped. A protected or path-installed
                 * package is not on Packagist, and "we could not check this one"
                 * is a true and useful thing to say — whereas leaving it out
                 * implies it is up to date, which is a guess presented as a fact.
                 */
                $result['uncheckable'][] = $name;
            } elseif (($newest = $this->newestComparable($versions, $version, $name, $rule)) !== null && $this->isNewer($newest, $version)) {
                $result['updates'][$name] = ['from' => $version, 'to' => $newest];

                // "What does this update give me?" (ClaudiusH, 2026-10-09). A
                // link to the release, never a guess: absent when the package
                // is not on GitHub.
                $notes = $this->releaseNotes[$name][$newest]
                    ?? ($notesFor ? ($notesFor($name)[$newest] ?? null) : null);

                if ($notes !== null) {
                    $result['updates'][$name]['notes'] = $notes;
                }
            }

            /*
             * Saved as it goes. "Check now" runs inside a web request, and a
             * PHP or gateway timeout part of the way through used to lose
             * every answer. Each save is complete on its own: what has been
             * asked, and the last check's answer for what has not.
             */
            $this->save($result);
        }

        $this->save($result);

        return $result;
    }

    protected function outOfTime(): bool
    {
        return $this->deadline !== null && microtime(true) >= $this->deadline;
    }

    /**
     * Written whole and then renamed into place, so a state poll reading it
     * part of the way through a check never sees half a file.
     *
     * @param array<string,mixed> $result
     */
    protected function save(array $result): void
    {
        /*
         * The directory is made here, not assumed. Nothing else creates
         * storage/extension-manager until the first update run, so on a fresh install
         * every check — the nightly one, the console one and "Check now" — wrote
         * into a directory that did not exist, the @ swallowed it, and the
         * screen said "Not checked yet" with no Update button anywhere, over a
         * check that had just reported eleven newer versions.
         */
        $dir = dirname($this->cachePath);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $tmp = $this->cachePath.'.'.bin2hex(random_bytes(4));

        if (@file_put_contents($tmp, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false || ! @rename($tmp, $this->cachePath)) {
            @unlink($tmp);
        }
    }

    /**
     * Packagist first, then the site's own protected repositories: the check the
     * console command and "Check now" both run.
     *
     * One place, because they drifted. "Check now" asked Packagist alone,
     * so pressing it dropped every protected extension's update that the nightly
     * check had found, until the next night put them back.
     *
     * @param array<string,string> $installed
     * @return array<string,mixed>
     */
    public function refreshWith(array $installed, PrivateIndex $protected, ?StabilityRule $rule = null, ?int $budget = null): array
    {
        return $this->refresh(
            $installed,
            function (string $name) use ($protected) {
                $public = $this->fromPackagist($name);

                // Still asked when Packagist is down: a protected package's
                // answer does not depend on it. Unknown to both, the package
                // is "could not ask" if Packagist could not be asked.
                return is_array($public) ? $public : ($protected->versionsFor($name) ?? $public);
            },
            fn (string $name) => $protected->releaseNotesFor($name),
            $rule,
            $budget,
        );
    }

    /**
     * The whole check for a forum: what its lock says is installed, filtered
     * as {@see interesting()}, asked of Packagist and then its protected
     * repositories. Null when composer.lock cannot be read.
     *
     * One place for "Check now", extension-manager:check and the re-check
     * extension-manager:update makes before giving up on a pin.
     *
     * @param int|null $budget seconds the check may take, for a caller inside
     *                         a web request; null to ask everything
     * @return array<string,mixed>|null
     */
    public function refreshSite(TranslatorInterface $translator, string $base, ?int $budget = null): ?array
    {
        $lock = (array) json_decode((string) @file_get_contents($base.'/composer.lock'), true);
        $packages = array_merge(array_values((array) ($lock['packages'] ?? [])), array_values((array) ($lock['packages-dev'] ?? [])));

        if ($packages === []) {
            return null;
        }

        return $this->refreshWith($this->interesting($packages, self::directRequires($base.'/composer.json')), new PrivateIndex(
            new Repositories($translator, new JsonFile($translator, $base.'/composer.json')),
            new AuthTokens($translator, new JsonFile($translator, $base.'/auth.json')),
            $this->http,
        ), StabilityRule::fromComposerJson($base.'/composer.json'), $budget);
    }

    /** @return array<string,mixed> */
    public function cached(): array
    {
        return self::cachedIn($this->cachePath);
    }

    /**
     * The saved check, for a reader that never asks anything new.
     *
     * @return array<string,mixed>
     */
    public static function cachedIn(string $cachePath): array
    {
        if (! is_file($cachePath)) {
            return ['checkedAt' => null, 'updates' => [], 'uncheckable' => [], 'tracking' => []];
        }

        return (array) json_decode((string) file_get_contents($cachePath), true);
    }

    /**
     * The cached check, minus what has been installed since.
     *
     * The check is saved when it runs and an update does not re-run it, so
     * straight after updating Mobile Tab to 2.0.1 its card still offered
     * "2.0.0 → 2.0.1" (ClaudiusH, 2026-10-07), and the Extension Manager offered itself the
     * version it was already running. Each entry is measured against what
     * composer.lock says now: dropped once reached, and its "from" kept true
     * when only part of the way.
     *
     * Dropped too when the package is no longer installed, or the forum no
     * longer allows a release that unfinished: either way Update cannot
     * deliver it, and `update --all` refused the whole run over the first.
     *
     * @return array<string,mixed>
     */
    public function current(string $lockPath): array
    {
        $cached = $this->cached();
        $lock = (array) json_decode((string) @file_get_contents($lockPath), true);
        $now = $this->interesting(
            array_merge((array) ($lock['packages'] ?? []), (array) ($lock['packages-dev'] ?? [])),
            self::directRequires(dirname($lockPath).'/composer.json')
        );
        $rule = StabilityRule::fromComposerJson(dirname($lockPath).'/composer.json');

        $updates = [];

        foreach ((array) ($cached['updates'] ?? []) as $name => $update) {
            $installed = $now[$name] ?? null;

            if ($installed === null || ! $rule->allows($name, (string) ($update['to'] ?? ''))) {
                continue;
            }

            if (self::tracksABranch($installed)) {
                $updates[$name] = $update;
            } elseif ($this->isNewer((string) ($update['to'] ?? ''), $installed)) {
                $updates[$name] = ['from' => $installed] + $update;
            }
        }

        return ['updates' => $updates] + $cached;
    }

    public function isStale(): bool
    {
        $at = $this->cached()['checkedAt'] ?? null;

        return $at === null || (time() - (int) $at) > $this->freshFor;
    }

    /** A branch install: `dev-main`, `1.x-dev`. */
    public static function tracksABranch(string $version): bool
    {
        return str_starts_with($version, 'dev-') || str_contains($version, '-dev');
    }

    /**
     * The newest version worth comparing against what is installed.
     *
     * A site on a dev branch is not offered a tagged release, and a site on a
     * stable tag is not offered a dev branch. Mixing them produces "update
     * available: dev-main" on a forum deliberately pinned to 2.0.0, which is
     * noise that trains people to ignore the badge.
     *
     * And only releases the forum lets Composer install. A release candidate
     * offered to a stable forum is a badge Update can never clear. Under
     * prefer-stable, Composer picks the most stable release there is, so only
     * the most stable ones are compared.
     *
     * @param list<string> $versions
     */
    protected function newestComparable(array $versions, string $installed, string $name = '', ?StabilityRule $rule = null): ?string
    {
        $installedIsDev = self::tracksABranch($installed);

        $candidates = array_values(array_filter($versions, function (string $v) use ($installedIsDev, $name, $rule) {
            return self::tracksABranch($v) === $installedIsDev && ($rule === null || $rule->allows($name, $v));
        }));

        if ($candidates === []) {
            return null;
        }

        if ($rule !== null && $rule->prefersStable()) {
            $best = min(array_map(StabilityRule::rank(...), $candidates));
            $candidates = array_values(array_filter($candidates, fn (string $v) => StabilityRule::rank($v) === $best));
        }

        usort($candidates, fn (string $a, string $b) => version_compare(
            $this->normalise($a),
            $this->normalise($b)
        ));

        return end($candidates) ?: null;
    }

    protected function isNewer(string $candidate, string $installed): bool
    {
        // Two dev branches of the same name are never "newer" than each other by
        // version string — whether a branch has moved is a question for the
        // resolve, not for this.
        if (str_starts_with($candidate, 'dev-')) {
            return false;
        }

        return version_compare($this->normalise($candidate), $this->normalise($installed)) > 0;
    }

    protected function normalise(string $version): string
    {
        return ltrim($version, 'v');
    }

    /**
     * Public so a caller can compose it — Packagist first, then whatever
     * protected repositories the site has — without reimplementing it.
     *
     * Three answers, because two of them used to be one. A 404 is Packagist
     * saying it does not know the package: null, "uncheckable". Anything else
     * that is not a readable list — a 5xx, a 429, a timeout, a body that does
     * not parse — is Packagist not answering: false, so the check keeps what
     * it knew instead of deciding there is nothing newer.
     *
     * @return list<string>|false|null
     */
    public function fromPackagist(string $name): array|false|null
    {
        // Never longer than the check has left, so a budget is kept even
        // when Packagist is slow rather than down.
        $timeout = $this->deadline === null ? 15 : max(1, min(15, $this->deadline - microtime(true)));

        try {
            $response = $this->http->request('GET', 'https://repo.packagist.org/p2/'.$name.'.json', [
                'timeout' => $timeout,
                'http_errors' => false,
                'headers' => ['User-Agent' => 'the Extension Manager'],
            ]);
        } catch (GuzzleException) {
            return false;
        }

        $status = $response->getStatusCode();

        if ($status === 404 || $status === 410) {
            return null;
        }

        $data = $status === 200 ? json_decode((string) $response->getBody(), true) : null;
        $versions = is_array($data) ? ($data['packages'][$name] ?? null) : null;

        if (! is_array($versions)) {
            return false;
        }

        [$list, $notes] = self::readP2($versions);
        $this->releaseNotes[$name] = $notes;

        return $list;
    }

    /**
     * The versions in a Packagist p2 response, and each one's GitHub release.
     *
     * p2 is MINIFIED: each entry repeats only what changed since the one
     * before it, and `__unset` removes a key. `source` is usually given once, on
     * the first entry, so reading each entry on its own found a repository for
     * the newest version and nothing for the rest. Expanded here as Composer's
     * MetadataMinifier does.
     *
     * The tag is the version as written (`v1.2.0` or `1.2.0`), which is the
     * name Packagist read from the repository.
     *
     * @param list<array<string,mixed>> $entries
     * @return array{0: list<string>, 1: array<string,string>}
     */
    public static function readP2(array $entries): array
    {
        $versions = [];
        $notes = [];
        $current = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry as $key => $value) {
                if ($value === '__unset') {
                    unset($current[$key]);
                } else {
                    $current[$key] = $value;
                }
            }

            if (! isset($current['version'])) {
                continue;
            }

            $version = (string) $current['version'];
            $versions[] = $version;

            $url = self::releaseUrl((string) ($current['source']['url'] ?? ''), $version);

            if ($url !== null) {
                $notes[$version] = $url;
            }
        }

        return [$versions, $notes];
    }

    /**
     * A tagged version's release page on GitHub, or null for anything else: a
     * branch has no release, and other hosts lay their pages out differently.
     */
    public static function releaseUrl(string $sourceUrl, string $version): ?string
    {
        if (self::tracksABranch($version)
            || ! preg_match('#^(?:https?://|git@)github\.com[/:]([\w.-]+)/([\w.-]+?)(?:\.git)?/?$#', $sourceUrl, $m)) {
            return null;
        }

        return 'https://github.com/'.$m[1].'/'.$m[2].'/releases/tag/'.rawurlencode($version);
    }
}
