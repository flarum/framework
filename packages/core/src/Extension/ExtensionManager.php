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
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\ExtensionWasUninstalled;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Database\Migrator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

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

    public function __construct(SettingsRepositoryInterface $config, Application $app, Migrator $migrator, Dispatcher $dispatcher, Filesystem $filesystem)
    {
        $this->config = $config;
        $this->app = $app;
        $this->migrator = $migrator;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
    }

    public function getInfo()
    {
        $extensionsDir = $this->getExtensionsDir();

        $dirs = array_diff(scandir($extensionsDir), ['.', '..']);
        $extensions = [];

        $installed = json_decode(file_get_contents(public_path('vendor/composer/installed.json')), true);

        foreach ($dirs as $dir) {
            if (file_exists($manifest = $extensionsDir . '/' . $dir . '/composer.json')) {
                $extension = json_decode(file_get_contents($manifest), true);

                if (empty($extension['name'])) {
                    continue;
                }

                if (isset($extension['extra']['flarum-extension']['icon'])) {
                    $icon = &$extension['extra']['flarum-extension']['icon'];

                    if ($file = array_get($icon, 'image')) {
                        $file = $extensionsDir . '/' . $dir . '/' . $file;

                        if (file_exists($file)) {
                            $mimetype = pathinfo($file, PATHINFO_EXTENSION) === 'svg'
                                ? 'image/svg+xml'
                                : finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
                            $data = file_get_contents($file);

                            $icon['backgroundImage'] = 'url(\'data:' . $mimetype . ';base64,' . base64_encode($data) . '\')';
                        }
                    }
                }

                foreach ($installed as $package) {
                    if ($package['name'] === $extension['name']) {
                        $extension['version'] = $package['version'];
                    }
                }

                $extension['id'] = $dir;

                $extensions[$dir] = $extension;
            }
        }

        return $extensions;
    }

    public function enable($extension)
    {
        if (! $this->isEnabled($extension)) {
            $enabled = $this->getEnabled();

            $enabled[] = $extension;

            $this->migrate($extension);

            $this->publishAssets($extension);

            $this->setEnabled($enabled);

            $this->dispatcher->fire(new ExtensionWasEnabled($extension));
        }
    }

    public function disable($extension)
    {
        $enabled = $this->getEnabled();

        if (($k = array_search($extension, $enabled)) !== false) {
            unset($enabled[$k]);

            $this->setEnabled($enabled);

            $this->dispatcher->fire(new ExtensionWasDisabled($extension));
        }
    }

    public function uninstall($extension)
    {
        $this->disable($extension);

        $this->migrateDown($extension);

        $this->unpublishAssets($extension);

        $this->dispatcher->fire(new ExtensionWasUninstalled($extension));
    }

    /**
     * Copy the assets from an extension's assets directory into public view.
     *
     * @param string $extension
     */
    protected function publishAssets($extension)
    {
        $this->filesystem->copyDirectory(
            $this->app->basePath().'/extensions/'.$extension.'/assets',
            $this->app->basePath().'/assets/extensions/'.$extension
        );
    }

    /**
     * Delete an extension's assets from public view.
     *
     * @param string $extension
     */
    protected function unpublishAssets($extension)
    {
        $this->filesystem->deleteDirectory($this->app->basePath().'/assets/extensions/'.$extension);
    }

    /**
     * Get the path to an extension's published asset.
     *
     * @param string $extension
     * @param string $path
     * @return string
     */
    public function getAsset($extension, $path)
    {
        return $this->app->basePath().'/assets/extensions/'.$extension.$path;
    }

    public function migrate($extension, $up = true)
    {
        $migrationDir = public_path('extensions/' . $extension . '/migrations');

        $this->app->bind('Illuminate\Database\Schema\Builder', function ($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        if ($up) {
            $this->migrator->run($migrationDir, $extension);
        } else {
            $this->migrator->reset($migrationDir, $extension);
        }
    }

    public function migrateDown($extension)
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
