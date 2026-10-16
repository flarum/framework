<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Discover;

use Composer\Semver\Semver;

/**
 * Whether a package has a release that says it works with this Flarum.
 *
 * 🚨 The single most useful thing discovery can do, and the thing the current
 * tooling does not. The previous Extension Manager lists everything on Packagist and lets you
 * find out at install time that an extension is Flarum 1 only — after the
 * resolve, after the wait, and with an error message about version constraints
 * rather than a sentence. Knowing before you press anything is most of the value.
 *
 * 🚨 Constraints are read with Composer's own parser, not a regular expression.
 * A Flarum extension's constraint is routinely `^1.8 || ^2.0`, or `>=1.8`, or
 * `^2.0@beta` — every hand-rolled matcher gets one of those wrong, and getting
 * it wrong means either hiding extensions that work or offering ones that
 * cannot. composer/composer is a dependency, so its parser is right here.
 *
 * This still says "declares", never "works". A declared constraint is the
 * author's claim, and whether the rest of your site allows it is a question only
 * the plan can answer.
 */
class Compatibility
{
    /**
     * @param string $coreVersion the flarum/core version installed, e.g. 2.0.0
     */
    public function __construct(private string $coreVersion = '2.0.0')
    {
    }

    public function coreVersion(): string
    {
        return $this->coreVersion;
    }

    /**
     * @param array<string,mixed> $p2 the decoded p2 metadata for one package
     * @return array{compatible:?bool, version:?string, requires:?string, stability:?string, replaced:bool, replacedBy:?string, source:?array<string,mixed>}
     */
    public function verdict(string $name, array $p2): array
    {
        $versions = $p2['packages'][$name] ?? null;

        if (! is_array($versions) || $versions === []) {
            // Not on Packagist, or nothing published. Unknown, and said so.
            return ['compatible' => null, 'version' => null, 'requires' => null, 'stability' => null, 'replaced' => false, 'replacedBy' => null, 'source' => null];
        }

        $versions = self::expand($versions, (string) ($p2['minified'] ?? ''));

        return $this->judge($name, $versions) + $this->replacement($name, $versions[0]);
    }

    /**
     * 🚨 Packagist's p2 files are minified: only the newest release is complete,
     * and every later entry lists just the keys that CHANGED from the one above
     * it, with "__unset" for a key that went away. Read raw, an older release
     * that inherits its flarum/core requirement looks like it has none and is
     * skipped, so a beta newer than a stable release with the same requirement
     * won the verdict. Expanded exactly as Composer's MetadataMinifier does.
     *
     * @param list<array<string,mixed>> $versions
     * @return list<array<string,mixed>>
     */
    public static function expand(array $versions, string $minified): array
    {
        if ($minified !== 'composer/2.0') {
            return array_values($versions);
        }

        $out = [];
        $current = null;

        foreach ($versions as $release) {
            if ($current === null) {
                $current = (array) $release;
            } else {
                foreach ((array) $release as $key => $value) {
                    if ($value === '__unset') {
                        unset($current[$key]);
                    } else {
                        $current[$key] = $value;
                    }
                }
            }

            $out[] = $current;
        }

        return $out;
    }

    /**
     * Whether this name is a dead end that another package took over.
     *
     * 🚨 Two shapes, and the previous Extension Manager catches neither (discuss.flarum.org
     * d/40012). One is an author marking it abandoned with a replacement. The
     * other is quieter: the repository moved to a new name, and its composer.json
     * now says it REPLACES the old one, so the old name on Packagist serves the
     * new package's code. v17development/flarum-seo is that, and installing it
     * gives you FoF SEO under the wrong id, which then refuses to enable over a
     * circular dependency. The tell is a release that replaces its own name; the
     * real name is resolved from the repository (see Packagist::verdicts()).
     *
     * @param array<string,mixed> $latest the newest release, expanded
     * @return array{replaced:bool, replacedBy:?string, source:?array<string,mixed>}
     */
    private function replacement(string $name, array $latest): array
    {
        $abandoned = $latest['abandoned'] ?? null;

        if (is_string($abandoned) && $abandoned !== '' && strtolower($abandoned) !== strtolower($name)) {
            return ['replaced' => true, 'replacedBy' => strtolower($abandoned), 'source' => null];
        }

        if (isset(array_change_key_case((array) ($latest['replace'] ?? []))[strtolower($name)])) {
            return ['replaced' => true, 'replacedBy' => null, 'source' => (array) ($latest['source'] ?? [])];
        }

        return ['replaced' => false, 'replacedBy' => null, 'source' => null];
    }

    /**
     * @param list<array<string,mixed>> $versions expanded
     * @return array{compatible:?bool, version:?string, requires:?string, stability:?string}
     */
    private function judge(string $name, array $versions): array
    {
        $best = null;

        foreach ($versions as $release) {
            $constraint = $release['require']['flarum/core'] ?? null;

            if (! is_string($constraint) || ! $this->admits($constraint)) {
                continue;
            }

            $candidate = [
                'version' => (string) ($release['version'] ?? ''),
                'requires' => $constraint,
                'stability' => $this->stability((string) ($release['version'] ?? '')),
            ];

            /*
             * 🚨 A stable release beats a newer unstable one. Packagist lists
             * versions newest-first, so taking the first match would offer a
             * beta to somebody who has never opted into one — and most forums
             * cannot install it anyway, because minimum-stability is stable.
             * A beta is only reported when it is the ONLY thing that works, and
             * the screen labels it as such.
             */
            if ($best === null || ($best['stability'] !== 'stable' && $candidate['stability'] === 'stable')) {
                $best = $candidate;
            }
        }

        if ($best === null) {
            return [
                'compatible' => false,
                'version' => null,
                'requires' => $this->latestConstraint($versions),
                'stability' => null,
            ];
        }

        return ['compatible' => true] + $best;
    }

    /**
     * Does this constraint admit the core version being asked about?
     *
     * Public because the core pre-flight answers most of its question straight
     * from composer.lock — the constraint of the release ALREADY installed —
     * without any Packagist metadata to feed through verdict().
     */
    public function admitsCore(string $constraint): bool
    {
        return $this->admits($constraint);
    }

    /**
     * Does this constraint admit the Flarum that is installed?
     *
     * 🚨 Catches UnexpectedValueException ONLY, and this narrowness is the whole
     * point. The first version caught \Throwable, which meant that when
     * composer/semver was missing from the autoloader the Error was swallowed
     * and every single package came back "not for this Flarum" — a confident,
     * wrong answer given to the user with no hint that nothing had been checked
     * at all. An infrastructure failure must never be able to disguise itself as
     * a verdict.
     *
     * An unparseable constraint IS a real verdict: the author wrote something
     * Composer cannot read, so Composer will not install it either.
     */
    private function admits(string $constraint): bool
    {
        try {
            return Semver::satisfies($this->coreVersion, $constraint);
        } catch (\UnexpectedValueException) {
            return false;
        }
    }

    /**
     * What the newest release asks for, so a "no" can say what it wanted rather
     * than only that it did not fit.
     *
     * @param array<int,mixed> $versions
     */
    private function latestConstraint(array $versions): ?string
    {
        foreach ($versions as $release) {
            $constraint = $release['require']['flarum/core'] ?? null;

            if (is_string($constraint)) {
                return $constraint;
            }
        }

        return null;
    }

    private function stability(string $version): string
    {
        $v = strtolower($version);

        foreach (['dev' => 'dev', 'alpha' => 'alpha', 'beta' => 'beta', 'rc' => 'rc'] as $needle => $label) {
            if (str_contains($v, $needle)) {
                return $label;
            }
        }

        return 'stable';
    }
}
