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
            $installedSet = [];

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
                $extension->calculateDependencies($installedSet);
            }

            $this->extensions = $extensions->sortBy(function ($extension, $name) {
                return $extension->composerJsonAttribute('extra.flarum-extension.title');
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

        $enabledIds[] = $name;

        $this->migrate($extension);

        $this->publishAssets($extension);

        $this->setEnabled($enabledIds);

        $extension->enable($this->container);

        $this->dispatcher->dispatch(new Enabled($extension));
    }

    /**
     * Disables an extension.
     *
     * @param string $name
     */
    public function disable($name)
    {
        $enabled = $this->getEnabled();

        if (($k = array_search($name, $enabled)) === false) {
            return;
        }

        $extension = $this->getExtension($name);

        $dependentExtensions = [];

        foreach ($this->getEnabledExtensions() as $possibleDependent) {
            if (in_array($extension->getId(), $possibleDependent->getExtensionDependencyIds())) {
                $dependentExtensions[] = $possibleDependent;
            }
        }

        if (! empty($dependentExtensions)) {
            throw new Exception\DependentExtensionsException($extension, $dependentExtensions);
        }

        $this->dispatcher->dispatch(new Disabling($extension));

        unset($enabled[$k]);

        $this->setEnabled($enabled);

        $extension->disable($this->container);

        $this->dispatcher->dispatch(new Disabled($extension));
    }

    /**
     * Uninstalls an extension.
     *
     * @param string $name
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
        if ($extension->hasAssets()) {
            $this->filesystem->copyDirectory(
                $extension->getPath().'/assets',
                $this->paths->public.'/assets/extensions/'.$extension->getId()
            );
        }
    }

    /**
     * Delete an extension's assets from public view.
     *
     * @param Extension $extension
     */
    protected function unpublishAssets(Extension $extension)
    {
        $this->filesystem->deleteDirectory($this->paths->public.'/assets/extensions/'.$extension->getId());
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
        return $this->paths->public.'/assets/extensions/'.$extension->getId().$path;
    }

    /**
     * Runs the database migrations for the extension.
     *
     * @param Extension $extension
     * @param string $direction
     * @return void
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
     * @param Container $app
     */
    public function extend(Container $app)
    {
        foreach ($this->getEnabledExtensions() as $extension) {
            $extension->extend($app);
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
     * @param array $enabled
     */
    protected function setEnabled(array $enabled)
    {
        $enabled = array_values(array_unique($enabled));

        $this->config->set('extensions_enabled', json_encode($enabled));
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
}
