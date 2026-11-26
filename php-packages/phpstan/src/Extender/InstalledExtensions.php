<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Flarum\Extension\Extension;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

final class InstalledExtensions implements ResultCacheMetaExtension
{
    /** @var array<string, true>|null */
    private ?array $ids = null;

    public function has(string $extensionId): bool
    {
        return isset($this->ids()[$extensionId]);
    }

    public function getKey(): string
    {
        return 'flarum-installed-extensions';
    }

    public function getHash(): string
    {
        return hash('sha256', implode("\n", array_keys($this->ids())));
    }

    /** @return array<string, true> */
    private function ids(): array
    {
        if ($this->ids === null) {
            $names = [];
            foreach (InstalledVersions::getAllRawData() as $installation) {
                foreach ($installation['versions'] as $name => $package) {
                    if (($package['type'] ?? null) === 'flarum-extension') {
                        $names[] = $name;
                    }
                }

                // PHPStan's own Composer installation can be the first dataset.
                $rootPath = $installation['root']['install_path'] ?? null;
                if ($rootPath !== null) {
                    $names = array_merge($names, $this->bundledExtensions($this->readManifest("$rootPath/composer.json"), $rootPath));
                }
            }

            foreach (array_keys(ClassLoader::getRegisteredLoaders()) as $vendorPath) {
                $installed = $this->readManifest("$vendorPath/composer/installed.json");
                foreach ($installed['packages'] ?? $installed as $package) {
                    if (empty($package['extra']['flarum-subextensions'])) {
                        continue;
                    }

                    $packagePath = isset($package['install-path'])
                        ? "$vendorPath/composer/{$package['install-path']}"
                        : "$vendorPath/{$package['name']}";
                    $names = array_merge($names, $this->bundledExtensions($package, $packagePath));
                }
            }

            $ids = array_fill_keys(array_map(Extension::nameToId(...), $names), true);
            ksort($ids);
            $this->ids = $ids;
        }

        return $this->ids;
    }

    /** @return list<string> */
    private function bundledExtensions(array $package, string $path): array
    {
        $names = [];

        foreach ($package['extra']['flarum-subextensions'] ?? [] as $directory) {
            $manifest = $this->readManifest("$path/$directory/composer.json");
            if (($manifest['type'] ?? null) === 'flarum-extension' && str_contains($manifest['name'] ?? '', '/')) {
                $names[] = $manifest['name'];
            }
        }

        return $names;
    }

    private function readManifest(string $path): array
    {
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($manifest) ? $manifest : [];
    }
}
