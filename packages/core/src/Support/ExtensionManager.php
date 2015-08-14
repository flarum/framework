<?php namespace Flarum\Support;

use Flarum\Support\ServiceProvider;
use Flarum\Core\Settings\SettingsRepository;
use Illuminate\Contracts\Container\Container;
use Flarum\Migrations\Migrator;

class ExtensionManager
{
    protected $config;

    protected $app;

    public function __construct(SettingsRepository $config, Container $app)
    {
        $this->config = $config;
        $this->app = $app;
    }

    public function getInfo()
    {
        $extensionsDir = $this->getExtensionsDir();

        $dirs = array_diff(scandir($extensionsDir), ['.', '..']);
        $extensions = [];

        foreach ($dirs as $dir) {
            if (file_exists($manifest = $extensionsDir . '/' . $dir . '/flarum.json')) {
                $extensions[] = json_decode(file_get_contents($manifest));
            }
        }

        return $extensions;
    }

    public function enable($extension)
    {
        $enabled = $this->getEnabled();

        if (! in_array($extension, $enabled)) {
            $enabled[] = $extension;

            $class = $this->load($extension);

            $class->install();

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

        $class = $this->load($extension);

        $class->uninstall();

        $this->migrate($extension, false);
    }

    protected function migrate($extension, $up = true)
    {
        $migrationDir = base_path('../extensions/' . $extension . '/migrations');

        $this->app->bind('Illuminate\Database\Schema\Builder', function($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->app->make('Flarum\Migrations\Migrator');

        if ($up) {
            $migrator->run($migrationDir, $extension);
        } else {
            $migrator->reset($migrationDir, $extension);
        }
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

    protected function load($extension)
    {
        if (file_exists($file = $this->getExtensionsDir() . '/' . $extension . '/bootstrap.php')) {
            $className = require $file;

            $class = new $className($this->app);
        }

        return $class;
    }

    protected function getExtensionsDir()
    {
        return public_path('extensions');
    }
}
