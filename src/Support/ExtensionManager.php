<?php namespace Flarum\Support;

use Flarum\Support\ServiceProvider;
use Flarum\Core\Settings\SettingsRepository;
use Illuminate\Contracts\Container\Container;

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

            // run migrations
            // vendor publish

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

        // run migrations
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
