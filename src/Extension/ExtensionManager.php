<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Core;
use Flarum\Database\Migrator;
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\ExtensionWasUninstalled;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ExtensionManager
{
    protected $config;

    protected $app;

    protected $migrator;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        SettingsRepositoryInterface $config,
        Application $app,
        Migrator $migrator,
        Dispatcher $dispatcher,
        Filesystem $filesystem
    ) {
        $this->config     = $config;
        $this->app        = $app;
        $this->migrator   = $migrator;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
    }

    /**
     * @return Collection
     */
    public function getExtensions()
    {
        $extensionsDir = $this->getExtensionsDir();

        $dirs       = array_diff(scandir($extensionsDir), ['.', '..']);
        $extensions = new Collection();

        $installed = json_decode(file_get_contents(public_path('vendor/composer/installed.json')), true);

        foreach ($dirs as $dir) {
            if (file_exists($manifest = $extensionsDir . '/' . $dir . '/composer.json')) {
                $extension = new Extension(
                    $extensionsDir . '/' . $dir,
                    json_decode(file_get_contents($manifest), true)
                );

                if (empty($extension->name)) {
                    continue;
                }

                foreach ($installed as $package) {
                    if ($package['name'] === $extension->name) {
                        $extension->setInstalled(true);
                        $extension->setVersion($package['version']);
                        $extension->setEnabled($this->isEnabled($dir));
                    }
                }

                $extensions->put($dir, $extension);
            }
        }

        return $extensions->sortBy(function ($extension, $name) {
            return $extension->composerJsonAttribute('extra.flarum-extension.title');
        });
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
        if (!$this->isEnabled($name)) {
            $extension = $this->getExtension($name);

            $enabled = $this->getEnabled();

            $enabled[] = $name;

            $this->migrate($extension);

            $this->publishAssets($extension);

            $this->setEnabled($enabled);

            $extension->setEnabled(true);

            $this->dispatcher->fire(new ExtensionWasEnabled($extension));
        }
    }

    /**
     * Disables an extension.
     *
     * @param string $name
     */
    public function disable($name)
    {
        $enabled = $this->getEnabled();

        if (($k = array_search($name, $enabled)) !== false) {
            unset($enabled[$k]);

            $extension = $this->getExtension($name);

            $this->setEnabled($enabled);

            $extension->setEnabled(false);

            $this->dispatcher->fire(new ExtensionWasDisabled($extension));
        }
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

        $this->dispatcher->fire(new ExtensionWasUninstalled($extension));
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
                $extension->getPath() . '/assets',
                $this->app->basePath() . '/assets/extensions/' . $extension->getId()
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
        $this->filesystem->deleteDirectory($this->app->basePath() . '/assets/extensions/' . $extension);
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
        return $this->app->basePath() . '/assets/extensions/' . $extension->getId() . $path;
    }

    /**
     * Runs the database migrations for the extension.
     *
     * @param Extension $extension
     * @param bool|true $up
     */
    public function migrate(Extension $extension, $up = true)
    {
        if ($extension->hasMigrations()) {
            $migrationDir = $extension->getPath() . '/migrations';

            $this->app->bind('Illuminate\Database\Schema\Builder', function ($container) {
                return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
            });

            if ($up) {
                $this->migrator->run($migrationDir, $extension);
            } else {
                $this->migrator->reset($migrationDir, $extension);
            }
        }
    }

    /**
     * Runs the database migrations to reset the database to its old state.
     *
     * @param Extension $extension
     */
    public function migrateDown(Extension $extension)
    {
        $this->migrate($extension, false);
    }

    public function getMigrator()
    {
        return $this->migrator;
    }

    protected function getEnabled()
    {
        $config = $this->config->get('extensions_enabled');

        return json_decode($config, true);
    }

    protected function setEnabled(array $enabled)
    {
        $enabled = array_values(array_unique($enabled));

        $this->config->set('extensions_enabled', json_encode($enabled));
    }

    public function isEnabled($extension)
    {
        return in_array($extension, $this->getEnabled());
    }

    protected function getExtensionsDir()
    {
        return public_path('extensions');
    }
}
