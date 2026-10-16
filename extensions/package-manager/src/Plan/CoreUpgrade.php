<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

use Flarum\ExtensionManager\Discover\Compatibility;

/**
 * What updating Flarum itself would do to the extensions already installed.
 *
 * 🚨 This is the whole reason updating core is treated differently from
 * updating anything else. An extension update that fails is one extension; a
 * core update that fails is the forum. And the failure is not hypothetical —
 * Composer will simply refuse to resolve, at the end of a long wait, with a
 * wall of constraint text naming packages rather than extensions. Somebody then
 * has to work out which of their thirty extensions is the problem.
 *
 * So the question is asked BEFORE anything is pressed, and answered per
 * extension, by name.
 *
 * 🚨 The cheap answer first, and usually it is the only one needed. Whether the
 * version you ALREADY have supports the target core is written in composer.lock
 * — no network, no Composer, exact. Only extensions that fail that test cost an
 * HTTP call to ask whether a newer release would fix it. On a forum where most
 * extensions are already current, this is nearly free.
 */
class CoreUpgrade
{
    /** The installed release already declares support. Nothing to do. */
    public const READY = 'ready';

    /** A newer release of this extension declares support; it comes along. */
    public const NEEDS_UPDATE = 'needs-update';

    /** Nothing published declares support. This is what would stop the upgrade. */
    public const BLOCKED = 'blocked';

    /** Not on Packagist, or installed from a local path. Unknowable from here. */
    public const UNKNOWN = 'unknown';

    /**
     * @param list<array<string,mixed>> $lockPackages composer.lock's packages
     * @param string $target the core version being considered, e.g. 2.1.0
     * @return array{target:string, verdicts:list<array<string,mixed>>, pending:list<string>, blocked:int, updating:int}
     */
    public function preflight(array $lockPackages, string $target): array
    {
        $compat = new Compatibility($target);
        $verdicts = [];
        $ask = [];

        foreach ($lockPackages as $package) {
            if (($package['type'] ?? '') !== 'flarum-extension') {
                continue;
            }

            $name = (string) ($package['name'] ?? '');
            $installed = (string) ($package['version'] ?? '');
            $constraint = $package['require']['flarum/core'] ?? null;

            if (is_string($constraint) && $compat->admitsCore($constraint)) {
                $verdicts[$name] = [
                    'package' => $name,
                    'installed' => $installed,
                    'state' => self::READY,
                    'requires' => $constraint,
                    'to' => null,
                ];
                continue;
            }

            /*
             * 🚨 Handed back to be asked about later, not asked about now. One
             * HTTP call per extension inside this loop is what made the first
             * version take 28 seconds on a real forum.
             */
            $ask[] = $name;
            $verdicts[$name] = [
                'package' => $name,
                'installed' => $installed,
                'state' => self::UNKNOWN,
                'requires' => is_string($constraint) ? $constraint : null,
                'to' => null,
            ];
        }

        $rows = $this->worstFirst(array_values($verdicts));

        return [
            'target' => $target,
            'verdicts' => $rows,
            'pending' => $ask,
            'blocked' => count(array_filter($rows, fn ($r) => $r['state'] === self::BLOCKED)),
            'updating' => count(array_filter($rows, fn ($r) => $r['state'] === self::NEEDS_UPDATE)),
        ];
    }

    /**
     * Fold Packagist's answers into rows the cheap pass could not clear.
     *
     * @param list<array<string,mixed>> $rows
     * @param array<string,array<string,mixed>> $verdicts
     * @return array{verdicts:list<array<string,mixed>>, blocked:int, updating:int}
     */
    public function merge(array $rows, array $verdicts): array
    {
        foreach ($rows as $i => $row) {
            $verdict = $verdicts[$row['package']] ?? null;

            if ($verdict === null) {
                continue;
            }

            if (($verdict['compatible'] ?? null) === true) {
                $rows[$i]['state'] = self::NEEDS_UPDATE;
                $rows[$i]['to'] = $verdict['version'] ?? null;
                $rows[$i]['stability'] = $verdict['stability'] ?? null;
            } elseif (($verdict['compatible'] ?? null) === false) {
                $rows[$i]['state'] = self::BLOCKED;
                $rows[$i]['requires'] = $verdict['requires'] ?? $rows[$i]['requires'];
            }

            /*
             * 🚨 A null verdict leaves the row UNKNOWN. "Not on Packagist" is not
             * "incompatible", and promoting it to blocked would tell somebody
             * their upgrade is impossible when it may be perfectly fine — which
             * is worse than saying nothing, because they would believe it.
             */
        }

        $rows = $this->worstFirst($rows);

        return [
            'verdicts' => $rows,
            'blocked' => count(array_filter($rows, fn ($r) => $r['state'] === self::BLOCKED)),
            'updating' => count(array_filter($rows, fn ($r) => $r['state'] === self::NEEDS_UPDATE)),
        ];
    }

    /**
     * 🚨 Blocked first, then needs-update, then the rest — and re-applied after
     * a merge, not only when the rows are built.
     *
     * The list exists to answer "what stops me", and burying two blockers among
     * thirty ready rows is the same as not answering. Sorting only in
     * preflight() looked right and was useless: at that point everything
     * unresolved is equally unknown, so the rows were alphabetical, and the
     * blockers stayed buried at the exact moment they became known.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function worstFirst(array $rows): array
    {
        $order = [self::BLOCKED => 0, self::UNKNOWN => 1, self::NEEDS_UPDATE => 2, self::READY => 3];

        usort($rows, function (array $a, array $b) use ($order) {
            $byState = ($order[$a['state']] ?? 9) <=> ($order[$b['state']] ?? 9);

            return $byState !== 0 ? $byState : strcasecmp($a['package'], $b['package']);
        });

        return $rows;
    }
}
