<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

/**
 * What changed between two composer.lock files.
 *
 * 🚨 This is why an update here is cheap. Composer's own install path rebuilds a
 * whole tree; comparing locks says that exactly three packages moved, so exactly
 * three get downloaded and exactly three get replaced. Everything the design
 * claims — a rollback proportional to the change, an apply measured in renames
 * rather than minutes — follows from the plan being this specific.
 *
 * It is also the screen people see before they commit to anything. "Upgrading
 * fof/pwa, and pulling psr/clock in with it, because fof/pwa 2.0.0-beta.4
 * requires it" is a sentence somebody can make a decision about. A progress bar
 * is not.
 */
class LockDiff
{
    /**
     * @param array<string,mixed> $before the current composer.lock, decoded
     * @param array<string,mixed> $after  the lock Composer would write
     * @return list<Change>
     */
    public function between(array $before, array $after): array
    {
        $old = $this->index($before);
        $new = $this->index($after);

        $changes = [];

        foreach ($new as $name => $version) {
            if (! isset($old[$name])) {
                $changes[] = new Change(Change::ADD, $name, null, $version);
            } elseif ($old[$name] !== $version) {
                $changes[] = new Change(Change::REPLACE, $name, $old[$name], $version);
            }
        }

        foreach ($old as $name => $version) {
            if (! isset($new[$name])) {
                $changes[] = new Change(Change::REMOVE, $name, $version, null);
            }
        }

        /*
         * Sorted by package name so the plan a user approves is the plan that
         * runs. Composer's ordering is not stable between invocations, and a
         * resumed run re-derives this list and carries on at a saved index — if
         * the order moved, it would redo one package and skip another.
         */
        usort($changes, fn (Change $a, Change $b) => strcmp($a->package, $b->package));

        return $changes;
    }

    /**
     * Where each changed package can be downloaded from, and what it should
     * hash to.
     *
     * @param array<string,mixed> $after
     * @return array<string,array{url:string,type:string,reference:?string,shasum:?string}>
     */
    public function sources(array $after): array
    {
        $out = [];

        foreach ($this->packages($after) as $package) {
            $dist = $package['dist'] ?? null;

            if (! is_array($dist) || empty($dist['url'])) {
                // A source-only package (a path repo, or one with no archive).
                // Named rather than skipped silently, so the fetch phase can say
                // which package it cannot get rather than failing vaguely.
                continue;
            }

            $out[(string) $package['name']] = [
                'url' => (string) $dist['url'],
                'type' => (string) ($dist['type'] ?? 'zip'),
                'reference' => isset($dist['reference']) ? (string) $dist['reference'] : null,
                'shasum' => isset($dist['shasum']) && $dist['shasum'] !== '' ? (string) $dist['shasum'] : null,
            ];
        }

        return $out;
    }

    /**
     * A one-line reason each package is in the plan.
     *
     * 🚨 "Because you asked for it" and "because something you asked for needs
     * it" are different, and the difference is the whole reason somebody reads
     * this screen. Anything that cannot be attributed says so rather than
     * inventing a plausible cause.
     *
     * @param list<Change> $changes
     * @param array<string,mixed> $after
     * @param list<string> $requested packages the user actually asked to change
     * @return array<string,string>
     */
    public function reasons(array $changes, array $after, array $requested): array
    {
        $requires = [];

        foreach ($this->packages($after) as $package) {
            $requires[(string) $package['name']] = array_keys((array) ($package['require'] ?? []));
        }

        $reasons = [];

        foreach ($changes as $change) {
            if (in_array($change->package, $requested, true)) {
                $reasons[$change->package] = 'you asked for this';
                continue;
            }

            $blamed = null;

            foreach ($requested as $want) {
                if (in_array($change->package, $requires[$want] ?? [], true)) {
                    $blamed = $want;
                    break;
                }
            }

            if ($blamed === null) {
                foreach ($requires as $who => $needs) {
                    if ($who !== $change->package && in_array($change->package, $needs, true)) {
                        $blamed = $who;
                        break;
                    }
                }
            }

            $reasons[$change->package] = $blamed === null
                ? 'pulled in by this update'
                : 'required by '.$blamed;
        }

        return $reasons;
    }

    /** @return array<string,string> package => version */
    private function index(array $lock): array
    {
        $out = [];

        foreach ($this->packages($lock) as $package) {
            if (isset($package['name'], $package['version'])) {
                $out[(string) $package['name']] = (string) $package['version'];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string,mixed>>
     *
     * Both sections, because a dev dependency that moves still changes what is
     * on disk, and a plan that omitted it would be wrong about what it is about
     * to do.
     */
    private function packages(array $lock): array
    {
        return array_merge(
            array_values((array) ($lock['packages'] ?? [])),
            array_values((array) ($lock['packages-dev'] ?? []))
        );
    }
}
