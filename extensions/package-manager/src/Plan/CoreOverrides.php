<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

/**
 * Extensions that replace part of Flarum itself.
 *
 * An extension can ship its own copy of one of Flarum's classes and have
 * Composer load it in place of Flarum's. Every check that reads `require`
 * passes, because the extension honestly declares the Flarum it was written
 * for; the copy is still frozen at that version. The first Flarum release that
 * changes the class breaks every page.
 *
 * Found for real: irmmr/flarum-ext-rtl loads its own
 * `Flarum\Frontend\Compiler\LessCompiler`, copied from 2.0.1, through
 * `autoload.files`. Flarum 2.0.2's Assets calls a method that copy does not
 * have, and the forum answered 500 to everything (ClaudiusH, 2026-10-18).
 *
 * Read from composer.lock plus the few files `autoload.files` names, so it
 * costs a handful of small reads, never a scan of vendor/.
 */
class CoreOverrides
{
    /** Bytes read from each file: a namespace and the classes in it are near the top. */
    protected const HEAD = 16384;

    /**
     * @param list<array<string,mixed>> $lockPackages composer.lock's packages
     * @return list<array{package:string, classes:list<string>}>
     */
    public function find(array $lockPackages, string $vendorDir): array
    {
        $vendorDir = rtrim($vendorDir, '/');
        $flarums = $this->flarumNamespaces($lockPackages, $vendorDir);
        $found = [];

        foreach ($lockPackages as $package) {
            $name = (string) ($package['name'] ?? '');

            // Flarum's own packages are where these classes belong.
            if ($name === '' || str_starts_with($name, 'flarum/')) {
                continue;
            }

            $autoload = (array) ($package['autoload'] ?? []);
            $classes = [];

            /*
             * A namespace mapped over one Flarum already fills. Composer tries the
             * longest prefix first, so it wins for every class it has a file for.
             * Only an overlap counts: `Flarum\Classifieds\` sits under Flarum's
             * name and replaces nothing, because Flarum has no such folder.
             */
            foreach (array_keys((array) ($autoload['psr-4'] ?? [])) as $prefix) {
                $namespace = rtrim((string) $prefix, '\\');

                if (self::isFlarums($namespace) && $this->flarumHas($flarums, $namespace, true)) {
                    $classes[] = $namespace.'\\*';
                }
            }

            $files = array_merge((array) ($autoload['files'] ?? []), (array) ($autoload['classmap'] ?? []));

            foreach ($files as $file) {
                // A classmap may name a directory; only single PHP files are read.
                if (! is_string($file) || ! str_ends_with($file, '.php') || str_contains($file, '..')) {
                    continue;
                }

                $path = $vendorDir.'/'.$name.'/'.ltrim($file, '/');

                foreach ($this->flarumClassesIn($path) as $class) {
                    if ($this->flarumHas($flarums, $class, false)) {
                        $classes[] = $class;
                    }
                }
            }

            if ($classes !== []) {
                $found[] = ['package' => $name, 'classes' => array_values(array_unique($classes))];
            }
        }

        return $found;
    }

    /**
     * Which of these packages replaces the class an error names, if any.
     *
     * @param list<array{package:string, classes:list<string>}> $overrides
     */
    public static function culpritFor(string $error, array $overrides): ?array
    {
        if (! preg_match_all('/Flarum(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+/', $error, $m)) {
            return null;
        }

        foreach ($overrides as $override) {
            foreach ($override['classes'] as $class) {
                foreach ($m[0] as $named) {
                    $wildcard = str_ends_with($class, '\\*');

                    if ($named === $class || ($wildcard && str_starts_with($named, substr($class, 0, -1)))) {
                        return ['package' => $override['package'], 'class' => $named];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Flarum's own namespaces and the folders they load from, from its packages' psr-4.
     *
     * @return array<string,list<string>> namespace => directories
     */
    protected function flarumNamespaces(array $lockPackages, string $vendorDir): array
    {
        $map = [];

        foreach ($lockPackages as $package) {
            $name = (string) ($package['name'] ?? '');

            if (! str_starts_with($name, 'flarum/')) {
                continue;
            }

            foreach ((array) ($package['autoload']['psr-4'] ?? []) as $prefix => $dirs) {
                foreach ((array) $dirs as $dir) {
                    $map[rtrim((string) $prefix, '\\')][] = $vendorDir.'/'.$name.'/'.trim((string) $dir, '/');
                }
            }
        }

        return $map;
    }

    /**
     * Whether Flarum itself has this class (or, for a namespace, this folder).
     *
     * @param array<string,list<string>> $flarums
     */
    protected function flarumHas(array $flarums, string $name, bool $namespace): bool
    {
        foreach ($flarums as $prefix => $dirs) {
            if ($name !== $prefix && ! str_starts_with($name, $prefix.'\\')) {
                continue;
            }

            $rest = str_replace('\\', '/', ltrim(substr($name, strlen($prefix)), '\\'));

            foreach ($dirs as $dir) {
                if ($namespace ? is_dir(rtrim($dir.'/'.$rest, '/')) : is_file($dir.'/'.$rest.'.php')) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    protected function flarumClassesIn(string $path): array
    {
        $head = @file_get_contents($path, false, null, 0, self::HEAD);

        if (! is_string($head) || ! preg_match('/^\s*namespace\s+([A-Za-z0-9_\\\\]+)\s*[;{]/m', $head, $ns) || ! self::isFlarums($ns[1])) {
            return [];
        }

        preg_match_all('/^\s*(?:(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $head, $m);

        return array_map(fn (string $class) => $ns[1].'\\'.$class, $m[1]);
    }

    protected static function isFlarums(string $namespace): bool
    {
        return $namespace === 'Flarum' || str_starts_with(ltrim($namespace, '\\'), 'Flarum\\');
    }
}
