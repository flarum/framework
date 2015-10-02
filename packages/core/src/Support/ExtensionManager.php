<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Support;

use Flarum\Core;
use Flarum\Support\ServiceProvider;
use Flarum\Core\Settings\SettingsRepository;
use Illuminate\Contracts\Container\Container;
use Flarum\Migrations\Migrator;

class ExtensionManager
{
    protected $config;

    protected $app;

    protected $migrator;

    public function __construct(SettingsRepository $config, Container $app, Migrator $migrator)
    {
        $this->config = $config;
        $this->app = $app;
        $this->migrator = $migrator;
    }

    public function getInfo()
    {
        $extensionsDir = $this->getExtensionsDir();

        $dirs = array_diff(scandir($extensionsDir), ['.', '..']);
        $extensions = [];

        $installed = json_decode(file_get_contents(public_path('vendor/composer/installed.json')), true);

        foreach ($dirs as $dir) {
            if (file_exists($manifest = $extensionsDir . '/' . $dir . '/composer.json')) {
                $extensions[$dir] = json_decode(file_get_contents($manifest), true);

                foreach ($extensions[$dir]['extra']['flarum-extension']['icon'] as &$style) {
                    $style = str_replace('{$assets}', Core::url().'/extensions/'.$dir.'/assets', $style);
                }

                foreach ($installed as $package) {
                    if ($package['name'] === $extensions[$dir]['name']) {
                        $extensions[$dir]['version'] = $package['version'];
                    }
                }
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

            $this->setEnabled($enabled);
        }
    }

    public function disable($extension)
    {
        $enabled = $this->getEnabled();

        if (($k = array_search($extension, $enabled)) !== false) {
            unset($enabled[$k]);

            $this->setEnabled($enabled);
        }
    }

    public function uninstall($extension)
    {
        $this->disable($extension);

        $this->migrateDown($extension);
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
