<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Composer\Semver\VersionParser;

/**
 * Which releases this forum's composer.json lets Composer install, read the
 * way Composer reads it.
 *
 * The update check and a raised pin both need the answer. Offering a release
 * candidate to a forum on `minimum-stability: stable` gives it a badge that
 * Update can never clear ("Nothing to modify in lock file"), and raising an
 * exact pin to that release candidate would move a production forum onto it,
 * because a pin that names a release candidate is itself permission to install
 * one.
 *
 * Composer's rules: a release is allowed when it is at least as stable as
 * `minimum-stability`, or as stable as the package's own flag in the root
 * `require`. The flag is explicit (`^2.0@beta`) or read from a version the
 * constraint names (`^2.0.0-beta.3` allows betas of that package).
 * `prefer-stable` does not forbid anything, but it makes Composer choose the
 * most stable release it can, so a newer beta never wins over a stable one.
 */
class StabilityRule
{
    /** Composer's order, most stable first. */
    protected const RANK = ['stable' => 0, 'RC' => 5, 'beta' => 10, 'alpha' => 15, 'dev' => 20];

    /**
     * @param array<string,int> $flags package => the least stable rank its
     *                                 own requirement allows
     */
    public function __construct(
        protected int $minimum = 0,
        protected array $flags = [],
        protected bool $preferStable = false,
    ) {
    }

    /**
     * The rule a composer.json sets. A missing or unreadable file is
     * Composer's default: stable only.
     */
    public static function fromComposerJson(string $path): self
    {
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $data = is_array($data) ? $data : [];

        $minimum = self::RANK[VersionParser::normalizeStability((string) ($data['minimum-stability'] ?? 'stable'))] ?? 0;
        $requires = array_merge((array) ($data['require'] ?? []), (array) ($data['require-dev'] ?? []));

        return new self($minimum, self::flags($requires, $minimum), (bool) ($data['prefer-stable'] ?? false));
    }

    public function allows(string $package, string $version): bool
    {
        return self::rank($version) <= max($this->minimum, $this->flags[strtolower($package)] ?? 0);
    }

    public function prefersStable(): bool
    {
        return $this->preferStable;
    }

    /** 0 for a stable release, higher the less finished it is. */
    public static function rank(string $version): int
    {
        return self::RANK[VersionParser::parseStability($version)] ?? self::RANK['dev'];
    }

    /**
     * Each root requirement's own stability flag, as Composer's
     * RootPackageLoader works it out. Reimplemented rather than called,
     * because Composer marks its version internal.
     *
     * @param array<mixed> $requires
     * @return array<string,int>
     */
    protected static function flags(array $requires, int $minimum): array
    {
        $flags = [];

        foreach ($requires as $name => $constraint) {
            if (! is_string($name) || ! is_string($constraint)) {
                continue;
            }

            $parts = [];

            foreach (preg_split('{\s*\|\|?\s*}', trim($constraint)) ?: [] as $or) {
                array_push($parts, ...(preg_split('{(?<!^|as|[=>< ,]) *(?<!-)[, ](?!-) *(?!,|as|$)}', $or) ?: []));
            }

            $name = strtolower($name);
            $explicit = null;

            foreach ($parts as $part) {
                if (preg_match('{^[^@]*?@(stable|rc|beta|alpha|dev)$}i', $part, $m)) {
                    $explicit = max($explicit ?? 0, self::RANK[VersionParser::normalizeStability($m[1])]);
                }
            }

            if ($explicit !== null) {
                $flags[$name] = $explicit;
                continue;
            }

            // A version written into the constraint counts only when it is
            // not stable and not more stable than the forum already allows,
            // as in Composer.
            foreach ($parts as $part) {
                $version = (string) preg_replace('{^([^,\s@]+) as .+$}', '$1', $part);

                if (preg_match('{^[^,\s@]+$}', $version) && ($rank = self::rank($version)) > 0 && $rank >= $minimum) {
                    $flags[$name] = max($flags[$name] ?? 0, $rank);
                }
            }
        }

        return $flags;
    }
}
