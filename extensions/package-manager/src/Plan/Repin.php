<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

use Flarum\ExtensionManager\Work\UpdateCheck;

/**
 * Which pinned requirements an update may raise, and to what.
 *
 * 🚨 The whole feature exists because pressing Update on a pinned package did
 * nothing and said Finished. `composer update vendor/name` against
 * `"vendor/name": "2.1.1"` answers "Nothing to modify in lock file" and exits
 * 0 — the constraint forbids the move, and nothing anywhere says so.
 *
 * Because the answer edits composer.json, the rules live in one place and are
 * the same whoever is asking — the admin screen or the console:
 *
 *   - **The target comes from this site's own check**, never from the caller.
 *     A version supplied by a client would be an arbitrary string written
 *     straight into composer.json: a short walk to a downgrade, or to a package
 *     nobody chose.
 *   - **Only where the constraint is an exact pin.** Raising a range would be
 *     rewriting a decision nobody asked to change.
 *   - **The site's own spelling is kept.** A forum that writes `v0.3.1` gets
 *     `v0.3.2`, not `0.3.2`: a constraint that changes shape reads as something
 *     nobody did on purpose.
 *
 * Asking is separate from allowing. This says what COULD be raised; the caller
 * still has to have been given permission — `repin: true` from the screen, or
 * `--repin` on the command line.
 */
final class Repin
{
    public function __construct(
        private string $composerJsonPath,
        private string $updatesJsonPath,
    ) {
    }

    /**
     * The pins standing in the way of updating these packages.
     *
     * @param list<string> $packages
     * @return array<string,array{from:string,to:string}> package => the exact
     *         constraint it is held at, and the version it would have to be
     *         raised to
     */
    public function pins(array $packages): array
    {
        $available = (array) $this->cachedUpdates();
        $out = [];

        foreach ($this->pinned($packages) as $package => $constraint) {
            $to = $available[$package]['to'] ?? null;

            /*
             * 🚨 Only a target NEWER than the pin. The check is saved when it
             * runs and an update does not re-run it, so straight after moving
             * to v1.21.3 it still said "→ v1.21.3": --repin "raised" v1.21.3 to
             * v1.21.3 and the run moved nothing (live box, 2026-10-13).
             */
            if (! is_string($to) || version_compare(ltrim($to, 'v'), ltrim($constraint, 'v'), '<=')) {
                continue;
            }

            $out[$package] = [
                'from' => $constraint,
                'to' => str_starts_with($constraint, 'v') && ! str_starts_with($to, 'v') ? 'v'.$to : $to,
            ];
        }

        return $out;
    }

    /**
     * Which of these composer.json holds at an exact version, whether or not
     * the check knows of anything newer.
     *
     * 🚨 A pinned package with no target is one an update CANNOT move: Composer
     * answers "Nothing to modify in lock file" and the run goes green. The
     * console asks this so it can re-check, or refuse, instead of starting it.
     *
     * @param list<string> $packages
     * @return array<string,string> package => its exact constraint
     */
    public function pinned(array $packages): array
    {
        $require = (array) ($this->readJson($this->composerJsonPath)['require'] ?? []);
        $out = [];

        foreach ($packages as $package) {
            $constraint = $require[$package] ?? null;

            // An exact version, and nothing with a range operator anywhere in it.
            if (is_string($constraint) && preg_match('/^v?\d+\.\d+\.\d+/', $constraint) && ! preg_match('/[\^~*|]|\s-\s/', $constraint)) {
                $out[$package] = $constraint;
            }
        }

        return $out;
    }

    /**
     * The same answer as {@see pins()}, in the shape a run is recorded with.
     *
     * @param list<string> $packages
     * @return array<string,string>
     */
    public function targetsFor(array $packages): array
    {
        return array_map(fn (array $pin): string => $pin['to'], $this->pins($packages));
    }

    /** @return array<string,mixed> */
    private function cachedUpdates(): array
    {
        return (array) ((new UpdateCheck($this->updatesJsonPath))->cached()['updates'] ?? []);
    }

    /** @return array<string,mixed> */
    private function readJson(string $path): array
    {
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($data) ? $data : [];
    }
}
