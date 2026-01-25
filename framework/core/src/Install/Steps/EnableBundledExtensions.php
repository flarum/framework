<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Install\Step;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class EnableBundledExtensions implements Step
{
    public const DEFAULT_ENABLED_EXTENSIONS = [
        'flarum-approval',
        'flarum-bbcode',
        'flarum-emoji',
        'flarum-lang-english',
        'flarum-flags',
        'flarum-likes',
        'flarum-lock',
        'flarum-markdown',
        'flarum-mentions',
        'flarum-statistics',
        'flarum-sticky',
        'flarum-subscriptions',
        'flarum-suspend',
        'flarum-tags',
    ];

    /**
     * @var string[]
     */
    private array $enabledExtensions;

    private Migrator $migrator;

    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly string $vendorPath,
        private readonly string $assetPath,
        ?array $enabledExtensions = null
    ) {
        $this->enabledExtensions = $enabledExtensions ?? self::DEFAULT_ENABLED_EXTENSIONS;
    }

    public function getMessage(): string
    {
        return 'Enabling bundled extensions';
    }

    public function run(): void
    {
        $extensions = ExtensionManager::resolveExtensionOrder($this->loadExtensions()->all())['valid'];

        foreach ($extensions as $extension) {
            $extension->migrate($this->getMigrator());
            $adapter = new LocalFilesystemAdapter($this->assetPath);
            $extension->copyAssetsTo(
                new FilesystemAdapter(new Filesystem($adapter), $adapter)
            );
        }

        $extensionNames = json_encode(array_map(function (Extension $extension) {
            return $extension->getId();
        }, $extensions));

        (new DatabaseSettingsRepository($this->database))->set('extensions_enabled', $extensionNames);
    }

    /**
     * @return Collection<string, Extension>
     */
    private function loadExtensions(): Collection
    {
        $json = file_get_contents("$this->vendorPath/composer/installed.json");
        $installed = json_decode($json, true);

        // Composer 2.0 changes the structure of the installed.json manifest
        $installed = $installed['packages'] ?? $installed;

        $installedExtensions = (new Collection($installed))
            ->filter(function ($package) {
                return Arr::get($package, 'type') === 'flarum-extension';
            })->filter(function ($package) {
                return ! empty(Arr::get($package, 'name'));
            })->map(function ($package) {
                $path = isset($package['install-path'])
                    ? "$this->vendorPath/composer/".$package['install-path']
                    : $this->vendorPath.'/'.Arr::get($package, 'name');

                $extension = new Extension($path, $package);
                $extension->setVersion(Arr::get($package, 'version'));

                return $extension;
            })->mapWithKeys(function (Extension $extension) {
                return [$extension->name => $extension];
            });

        return $installedExtensions->filter(function (Extension $extension) {
            return in_array($extension->getId(), $this->enabledExtensions);
        })->map(function (Extension $extension) use ($installedExtensions) {
            $extension->calculateDependencies($installedExtensions->map(function () {
                return true;
            })->toArray());

            return $extension;
        });
    }

    private function getMigrator(): Migrator
    {
        return $this->migrator ??= new Migrator(
            new DatabaseMigrationRepository($this->database, 'migrations'),
            $this->database,
            new \Illuminate\Filesystem\Filesystem
        );
    }
}
