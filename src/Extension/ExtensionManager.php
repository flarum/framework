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
    protected $config;

    /**
     * @var Paths
     */
    protected $paths;

    protected $container;

    protected $migrator;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Collection|null
     */
    protected $extensions;

    public function __construct(
        SettingsRepositoryInterface $config,
        Paths $paths,
        Container $container,
        Migrator $migrator,
        Dispatcher $dispatcher,
        Filesystem $filesystem
    ) {
        $this->config = $config;
        $this->paths = $paths;
        $this->container = $container;
        $this->migrator = $migrator;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
    }

    /**
     * @return Collection
     */
    public function getExtensions()
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
            // We do the same for enabled extensions, for optional dependencies.
            $installedSet = [];
            $enabledIds = array_flip($this->getEnabled());

            foreach ($installed as $package) {
                if (Arr::get($package, 'type') != 'flarum-extension' || empty(Arr::get($package, 'name'))) {
                    continue;
                }

                $installedSet[Arr::get($package, 'name')] = true;

                $path = isset($package['install-path'])
                    ? $this->paths->vendor.'/composer/'.$package['install-path']
                    : $this->paths->vendor.'/'.Arr::get($package, 'name');

                // Instantiates an Extension object using the package path and composer.json file.
                $extension = new Extension($path, $package);

                // Per default all extensions are installed if they are registered in composer.
                $extension->setInstalled(true);
                $extension->setVersion(Arr::get($package, 'version'));

                $extensions->put($extension->getId(), $extension);
            }

            foreach ($extensions as $extension) {
                $extension->calculateDependencies($installedSet, $enabledIds);
            }

            $needsReset = false;
            $enabledExtensions = [];
            foreach ($this->getEnabled() as $enabledKey) {
                $extension = $extensions->get($enabledKey);
                if (is_null($extension)) {
                    $needsReset = true;
                } else {
                    $enabledExtensions[] = $extension;
                }
            }

            if ($needsReset) {
                $this->setEnabledExtensions($enabledExtensions);
            }

            $this->extensions = $extensions->sortBy(function ($extension, $name) {
                return $extension->getTitle();
            });
        }

        return $this->extensions;
    }

    /**
     * Loads an Extension with all information.
     *
     * @param string $name
     * @return Extension|null
     */
    public function getExtension($name)
    {
        return $this->getExtensions()->get($name);
    }

    /**
     * Enables the extension.
     *
     * @param string $name
     *
     * @internal
     */
    public function enable($name)
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
     * Disables an extension.
     *
     * @param string $name
     *
     * @internal
     */
    public function disable($name)
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
     * Uninstalls an extension.
     *
     * @param string $name
     * @internal
     */
    public function uninstall($name)
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
     *
     * @param Extension $extension
     */
    protected function publishAssets(Extension $extension)
    {
        $extension->copyAssetsTo($this->getAssetsFilesystem());
    }

    /**
     * Delete an extension's assets from public view.
     *
     * @param Extension $extension
     */
    protected function unpublishAssets(Extension $extension)
    {
        $this->getAssetsFilesystem()->deleteDirectory('extensions/'.$extension->getId());
    }

    /**
     * Get the path to an extension's published asset.
     *
     * @param Extension $extension
     * @param string    $path
     * @return string
     */
    public function getAsset(Extension $extension, $path)
    {
        return $this->getAssetsFilesystem()->url($extension->getId()."/$path");
    }

    /**
     * Get an instance of the assets filesystem.
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
     * @param Extension $extension
     * @param string $direction
     * @return void
     *
     * @internal
     */
    public function migrate(Extension $extension, $direction = 'up')
    {
        $this->container->bind(Builder::class, function ($container) {
            return $container->make(ConnectionInterface::class)->getSchemaBuilder();
        });

        $extension->migrate($this->migrator, $direction);
    }

    /**
     * Runs the database migrations to reset the database to its old state.
     *
     * @param Extension $extension
     * @return array Notes from the migrator.
     *
     * @internal
     */
    public function migrateDown(Extension $extension)
    {
        return $this->migrate($extension, 'down');
    }

    /**
     * The database migrator.
     *
     * @return Migrator
     */
    public function getMigrator()
    {
        return $this->migrator;
    }

    /**
     * Get only enabled extensions.
     *
     * @return array|Extension[]
     */
    public function getEnabledExtensions()
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
     *
     * @param Container $container
     */
    public function extend(Container $container)
    {
        foreach ($this->getEnabledExtensions() as $extension) {
            $extension->extend($container);
        }
    }

    /**
     * The id's of the enabled extensions.
     *
     * @return array
     */
    public function getEnabled()
    {
        return json_decode($this->config->get('extensions_enabled'), true) ?? [];
    }

    /**
     * Persist the currently enabled extensions.
     *
     * @param array $enabledExtensions
     */
    protected function setEnabledExtensions(array $enabledExtensions)
    {
        $sortedEnabled = static::resolveExtensionOrder($enabledExtensions)['valid'];

        $sortedEnabledIds = array_map(function (Extension $extension) {
            return $extension->getId();
        }, $sortedEnabled);

        $this->config->set('extensions_enabled', json_encode($sortedEnabledIds));
    }

    /**
     * Whether the extension is enabled.
     *
     * @param $extension
     * @return bool
     */
    public function isEnabled($extension)
    {
        $enabled = $this->getEnabledExtensions();

        return isset($enabled[$extension]);
    }

    /**
     * Returns the titles of the extensions passed.
     *
     * @param array $exts
     * @return string[]
     */
    public static function pluckTitles(array $exts)
    {
        return array_map(function (Extension $extension) {
            return $extension->getTitle();
        }, $exts);
    }

    /**
     * Sort a list of extensions so that they are properly resolved in respect to order.
     * Effectively just topological sorting.
     *
     * @param Extension[] $extensionList
     *
     * @return array with 2 keys: 'valid' points to an ordered array of \Flarum\Extension\Extension
     *                            'missingDependencies' points to an associative array of extensions that could not be resolved due
     *                                to missing dependencies, in the format extension id => array of missing dependency IDs.
     *                            'circularDependencies' points to an array of extensions ids of extensions
     *                                that cannot be processed due to circular dependencies
     *
     * @internal
     */
    public static function resolveExtensionOrder($extensionList)
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
            if ($count != 0) {
                $circularDependencies[] = $id;
            }
        }

        return [
            'valid' => $validExtensions,
            'missingDependencies' => $missingDependencies,
            'circularDependencies' => $circularDependencies
        ];
    }
}
