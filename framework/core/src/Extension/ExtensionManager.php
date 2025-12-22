<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Database\Migrator;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Disabling;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Event\Enabling;
use Flarum\Extension\Event\Uninstalled;
use Flarum\Extension\Exception\CircularDependenciesException;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ExtensionManager
{
    protected ?Collection $extensions = null;

    public function __construct(
        protected SettingsRepositoryInterface $config,
        protected Paths $paths,
        protected Container $container,
        protected Migrator $migrator,
        protected Dispatcher $dispatcher,
        protected Filesystem $filesystem,
        protected MaintenanceMode $maintenance,
    ) {
    }

    public function getExtensions(): Collection
    {
        if (is_null($this->extensions) && $this->filesystem->exists($this->paths->vendor.'/composer/installed.json')) {
            $extensions = new Collection();

            // Load all packages installed by composer.
            $installed = json_decode($this->filesystem->get($this->paths->vendor.'/composer/installed.json'), true);

            // Composer 2.0 changes the structure of the installed.json manifest
            $installed = $installed['packages'] ?? $installed;

            // We calculate and store a set of composer package names for all installed Flarum extensions,
            // so we know what is and isn't a flarum extension in `calculateDependencies`.
            // Using keys of an associative array allows us to do these checks in constant time.
            $installedSet = [];

            $composerJsonConfs = [];

            foreach ($installed as $package) {
                $name = Arr::get($package, 'name');
                if (empty($name)) {
                    continue;
                }

                $packagePath = isset($package['install-path'])
                    ? $this->paths->vendor.'/composer/'.$package['install-path']
                    : $this->paths->vendor.'/'.$name;

                if (Arr::get($package, 'type') === 'flarum-extension') {
                    $composerJsonConfs[$packagePath] = $package;
                }

                if ($subExtConfs = $this->subExtensionConfsFromJson($package, $packagePath)) {
                    $composerJsonConfs = array_merge($composerJsonConfs, $subExtConfs);
                }
            }

            foreach ($composerJsonConfs as $path => $package) {
                $installedSet[Arr::get($package, 'name')] = true;
                $extension = $this->extensionFromJson($package, $path);
                $extensions->put($extension->getId(), $extension);
            }

            /** @var Extension $extension */
            foreach ($extensions as $extension) {
                $extension->calculateDependencies($installedSet);
            }

            $needsReset = false;
            $enabledExtensions = [];
            foreach ($this->getEnabled() as $enabledKey) {
                $extension = $extensions->get($enabledKey);
                if (is_null($extension)) {
                    $needsReset = true;
                } else { // @phpstan-ignore-line
                    $enabledExtensions[] = $extension;
                }
            }

            if ($needsReset) {
                $this->setEnabledExtensions($enabledExtensions);
            }

            $this->extensions = $extensions->sortBy(function ($extension) {
                return $extension->getTitle();
            });
        }

        return $this->extensions;
    }

    public function getExtensionsById(array $ids): Collection
    {
        return $this->getExtensions()->filter(function (Extension $extension) use ($ids) {
            return in_array($extension->getId(), $ids);
        });
    }

    public function getExtension(string $name): ?Extension
    {
        return $this->getExtensions()->get($name);
    }

    /**
     * @internal
     */
    public function enable(string $name): void
    {
        if ($this->isEnabled($name)) {
            return;
        }

        $extension = $this->getExtension($name);

        $missingDependencies = [];
        $enabledIds = $this->getEnabled();
        foreach ($extension->getExtensionDependencyIds() as $dependencyId) {
            if (! in_array($dependencyId, $enabledIds)) {
                $missingDependencies[] = $this->getExtension($dependencyId);
            }
        }

        if (! empty($missingDependencies)) {
            throw new Exception\MissingDependenciesException($extension, $missingDependencies);
        }

        $this->dispatcher->dispatch(new Enabling($extension));

        $this->migrate($extension);

        $this->publishAssets($extension);

        $enabledExtensions = $this->getEnabledExtensions();
        $enabledExtensions[] = $extension;
        $this->setEnabledExtensions($enabledExtensions);

        $extension->enable($this->container);

        $this->dispatcher->dispatch(new Enabled($extension));
    }

    /**
     * @internal
     */
    public function disable(string $name): void
    {
        $extension = $this->getExtension($name);
        $enabledExtensions = $this->getEnabledExtensions();

        if (($k = array_search($extension, $enabledExtensions)) === false) {
            return;
        }

        $dependentExtensions = [];

        foreach ($enabledExtensions as $possibleDependent) {
            if (in_array($extension->getId(), $possibleDependent->getExtensionDependencyIds())) {
                $dependentExtensions[] = $possibleDependent;
            }
        }

        if (! empty($dependentExtensions)) {
            throw new Exception\DependentExtensionsException($extension, $dependentExtensions);
        }

        $this->dispatcher->dispatch(new Disabling($extension));

        unset($enabledExtensions[$k]);
        $this->setEnabledExtensions($enabledExtensions);

        $extension->disable($this->container);

        $this->dispatcher->dispatch(new Disabled($extension));
    }

    /**
     * @internal
     */
    public function uninstall(string $name): void
    {
        $extension = $this->getExtension($name);

        $this->disable($name);

        $this->migrateDown($extension);

        $this->unpublishAssets($extension);

        $extension->setInstalled(false);

        $this->dispatcher->dispatch(new Uninstalled($extension));
    }

    /**
     * Copy the assets from an extension's assets directory into public view.
     */
    protected function publishAssets(Extension $extension): void
    {
        $extension->copyAssetsTo($this->getAssetsFilesystem());
    }

    /**
     * Delete an extension's assets from public view.
     */
    protected function unpublishAssets(Extension $extension): void
    {
        $this->getAssetsFilesystem()->deleteDirectory('extensions/'.$extension->getId());
    }

    /**
     * Get the path to an extension's published asset.
     */
    public function getAsset(Extension $extension, string $path): string
    {
        return $this->getAssetsFilesystem()->url($extension->getId()."/$path");
    }

    /**
     * Get an instance of the `assets` filesystem.
     * This is resolved dynamically because Flarum's filesystem configuration
     * might not be booted yet when the ExtensionManager singleton initializes.
     */
    protected function getAssetsFilesystem(): Cloud
    {
        return resolve('filesystem')->disk('flarum-assets');
    }

    /**
     * Runs the database migrations for the extension.
     *
     * @internal
     */
    public function migrate(Extension $extension, string $direction = 'up'): ?int
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        return $extension->migrate($this->migrator, $direction);
    }

    /**
     * Runs the database migrations to reset the database to its old state.
     *
     * @internal
     */
    public function migrateDown(Extension $extension): void
    {
        $this->migrate($extension, 'down');
    }

    /**
     * The database migrator.
     */
    public function getMigrator(): Migrator
    {
        return $this->migrator;
    }

    /**
     * @return Extension[]
     */
    public function getEnabledExtensions(): array
    {
        $enabled = [];
        $extensions = $this->getExtensions();

        foreach ($this->getEnabled() as $id) {
            if (isset($extensions[$id])) {
                $enabled[$id] = $extensions[$id];
            }
        }

        return $enabled;
    }

    /**
     * Call on all enabled extensions to extend the Flarum application.
     */
    public function extend(Container $container): void
    {
        $extensions = $this->getEnabledExtensions();

        if ($this->maintenance->inSafeMode()) {
            $safeModeExtensions = $this->maintenance->safeModeExtensions();

            $extensions = array_filter($extensions, function (Extension $extension) use ($safeModeExtensions) {
                return in_array($extension->getId(), $safeModeExtensions, true);
            });

            $extensions = $this->sortDependencies($extensions);
        }

        foreach ($extensions as $extension) {
            $extension->extend($container);
        }
    }

    /**
     * The id's of the enabled extensions.
     *
     * @return string[]
     */
    public function getEnabled(): array
    {
        return json_decode($this->config->get('extensions_enabled'), true) ?? [];
    }

    /**
     * Persist the currently enabled extensions.
     *
     * @param Extension[] $enabledExtensions
     * @throws CircularDependenciesException
     */
    protected function setEnabledExtensions(array $enabledExtensions): void
    {
        $this->config->set('extensions_enabled', json_encode(array_map(function (Extension $extension) {
            return $extension->getId();
        }, $this->sortDependencies($enabledExtensions))));
    }

    /**
     * Apply a topological sort to the extensions to ensure that they are in the correct order.
     *
     * @param Extension[] $extensions
     * @return Extension[]
     * @throws CircularDependenciesException
     */
    public function sortDependencies(array $extensions): array
    {
        $resolved = static::resolveExtensionOrder($extensions);

        if (! empty($resolved['circularDependencies'])) {
            throw new Exception\CircularDependenciesException(
                $this->getExtensionsById($resolved['circularDependencies'])->values()->all()
            );
        }

        return $resolved['valid'];
    }

    public function isEnabled(string $extension): bool
    {
        $enabled = $this->getEnabledExtensions();

        return isset($enabled[$extension]);
    }

    /**
     * Returns the titles of the extensions passed.
     *
     * @param Extension[] $extensions
     * @return string[]
     */
    public static function pluckTitles(array $extensions): array
    {
        return array_map(function (Extension $extension) {
            return $extension->getTitle();
        }, $extensions);
    }

    /**
     * Sort a list of extensions so that they are properly resolved in respect to order.
     * Effectively just topological sorting.
     *
     * @param Extension[] $extensionList
     *
     * @return array{valid: Extension[], missingDependencies: array<string, string[]>, circularDependencies: string[]}
     *      'valid' points to an ordered array of \Flarum\Extension\Extension
     *      'missingDependencies' points to an associative array of extensions that could not be resolved due
     *          to missing dependencies, in the format extension id => array of missing dependency IDs.
     *      'circularDependencies' points to an array of extensions ids of extensions
     *          that cannot be processed due to circular dependencies
     *
     * @internal
     */
    public static function resolveExtensionOrder(array $extensionList): array
    {
        $extensionIdMapping = []; // Used for caching so we don't rerun ->getExtensions every time.

        // This is an implementation of Kahn's Algorithm (https://dl.acm.org/doi/10.1145/368996.369025)
        $extensionGraph = [];
        $output = [];
        $missingDependencies = []; // Extensions are invalid if they are missing dependencies, or have circular dependencies.
        $circularDependencies = [];
        $pendingQueue = [];
        $inDegreeCount = []; // How many extensions are dependent on a given extension?

        // Sort alphabetically by ID. This guarantees that any set of extensions will always be sorted the same way.
        // This makes boot order deterministic, and independent of enabled order.
        $extensionList = Arr::sort($extensionList, function ($ext) {
            return $ext->getId();
        });

        foreach ($extensionList as $extension) {
            $extensionIdMapping[$extension->getId()] = $extension;
        }

        /** @var Extension $extension */
        foreach ($extensionList as $extension) {
            $optionalDependencies = array_filter($extension->getOptionalDependencyIds(), function ($id) use ($extensionIdMapping) {
                return array_key_exists($id, $extensionIdMapping);
            });
            $extensionGraph[$extension->getId()] = array_merge($extension->getExtensionDependencyIds(), $optionalDependencies);

            foreach ($extensionGraph[$extension->getId()] as $dependency) {
                $inDegreeCount[$dependency] = array_key_exists($dependency, $inDegreeCount) ? $inDegreeCount[$dependency] + 1 : 1;
            }
        }

        foreach ($extensionList as $extension) {
            if (! array_key_exists($extension->getId(), $inDegreeCount)) {
                $inDegreeCount[$extension->getId()] = 0;
                $pendingQueue[] = $extension->getId();
            }
        }

        while (! empty($pendingQueue)) {
            $activeNode = array_shift($pendingQueue);
            $output[] = $activeNode;

            foreach ($extensionGraph[$activeNode] as $dependency) {
                $inDegreeCount[$dependency] -= 1;

                if ($inDegreeCount[$dependency] === 0) {
                    if (! array_key_exists($dependency, $extensionGraph)) {
                        // Missing Dependency
                        $missingDependencies[$activeNode] = array_merge(
                            Arr::get($missingDependencies, $activeNode, []),
                            [$dependency]
                        );
                    } else {
                        $pendingQueue[] = $dependency;
                    }
                }
            }
        }

        $validOutput = array_filter($output, function ($extension) use ($missingDependencies) {
            return ! array_key_exists($extension, $missingDependencies);
        });

        $validExtensions = array_reverse(array_map(function ($extensionId) use ($extensionIdMapping) {
            return $extensionIdMapping[$extensionId];
        }, $validOutput)); // Reversed as required by Kahn's algorithm.

        foreach ($inDegreeCount as $id => $count) {
            if ($count !== 0) {
                $circularDependencies[] = $id;
            }
        }

        return [
            'valid' => $validExtensions,
            'missingDependencies' => $missingDependencies,
            'circularDependencies' => $circularDependencies
        ];
    }

    protected function extensionFromJson(array $package, string $path): Extension
    {
        // Instantiates an Extension object using the package path and composer.json file.
        $extension = new Extension($path, $package);

        // Per default all extensions are installed if they are registered in composer.
        $extension->setInstalled(true);
        $extension->setVersion(Arr::get($package, 'version', '0.0'));

        return $extension;
    }

    protected function subExtensionConfsFromJson(array $package, string $packagePath): ?array
    {
        if (! ($subExtPaths = Arr::get($package, 'extra.flarum-subextensions', []))) {
            return null;
        }

        $subExtConfs = [];

        foreach ($subExtPaths as $subExtPath) {
            $subPackagePath = "$packagePath/$subExtPath";
            $conf = json_decode($this->filesystem->get("$subPackagePath/composer.json"), true);

            if (Arr::get($conf, 'type') === 'flarum-extension') {
                $subExtConfs[$subPackagePath] = $conf;
            }
        }

        return $subExtConfs;
    }
}
