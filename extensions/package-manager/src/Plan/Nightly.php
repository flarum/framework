<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

/**
 * Moving Flarum to its nightly build: which requirements change, and to what.
 *
 * 🚨 The update check never offers a branch to a forum on a tagged release —
 * rightly, as a badge — so a nightly cannot come from it. And the forum's own
 * composer.json cannot reach one either: `flarum/core: ^2.0.0-rc.8` with the
 * bundled extensions at `*` under `minimum-stability: beta` and
 * `prefer-stable` will never resolve to `2.x-dev`. Each requirement has to be
 * raised to the branch explicitly, which is also what grants that one package
 * dev stability.
 *
 * Core and the bundled extensions are split from one monorepo and move
 * together: core alone on nightly beside rc extensions is a combination
 * nobody tested. So every `flarum/*` package this forum REQUIRES that
 * publishes `{major}.x-dev` is moved, and nothing else. The branch name is
 * worked out here, on the server, from the installed core and Packagist —
 * never taken from a request.
 *
 * Undo is unchanged: composer.json is saved before the requirements are
 * raised, and putting it back returns the forum to the release it was on.
 */
final class Nightly
{
    /** @var callable(string):?array */
    private $fetch;

    public function __construct(private string $composerJsonPath, private string $lockPath, ?callable $fetch = null)
    {
        $this->fetch = $fetch ?? fn (string $name) => self::devVersions($name);
    }

    /** The branch this forum's Flarum would track, e.g. `2.x-dev`, or null if core cannot be read. */
    public function branch(): ?string
    {
        $lock = (array) json_decode((string) @file_get_contents($this->lockPath), true);

        foreach ((array) ($lock['packages'] ?? []) as $package) {
            if (($package['name'] ?? '') === 'flarum/core' && preg_match('/^v?(\d+)\./', (string) ($package['version'] ?? ''), $m)) {
                return $m[1].'.x-dev';
            }
        }

        return null;
    }

    /** @return array<string,string> package => the branch it would be required at */
    public function targets(): array
    {
        $branch = $this->branch();
        $require = (array) (json_decode((string) @file_get_contents($this->composerJsonPath), true)['require'] ?? []);

        if ($branch === null || ! isset($require['flarum/core'])) {
            return [];
        }

        $out = [];

        foreach (array_keys($require) as $name) {
            if (! str_starts_with((string) $name, 'flarum/')) {
                continue;
            }

            $versions = ($this->fetch)((string) $name);

            if (is_array($versions) && in_array($branch, $versions, true)) {
                $out[(string) $name] = $branch;
            }
        }

        // Core decides: without its branch there is no nightly to move to.
        return isset($out['flarum/core']) ? $out : [];
    }

    /** @return list<string>|null the package's branch versions on Packagist, null when unreachable */
    public static function devVersions(string $name): ?array
    {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'The Extension Manager (+https://github.com/flarum/extension-manager)']]);
        $json = @file_get_contents('https://repo.packagist.org/p2/'.$name.'~dev.json', false, $ctx);
        $data = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($data)) {
            return null;
        }

        return array_values(array_map(fn ($v) => (string) ($v['version'] ?? ''), (array) ($data['packages'][$name] ?? [])));
    }
}
