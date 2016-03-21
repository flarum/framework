<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Debug\Console;

use Flarum\Console\Command\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;

class InfoCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param ExtensionManager $extensions
     * @param array $config
     */
    public function __construct(ExtensionManager $extensions, array $config)
    {
        $this->extensions = $extensions;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription("Gather information about Flarum's core and installed extensions");
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Flarum core '.Application::VERSION);

        $this->info('PHP '.PHP_VERSION);

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            /** @var \Flarum\Extension\Extension $extension */
            $name = $extension->getId();
            $version = $extension->getVersion();

            $this->info("EXT $name $version");
        }

        $this->info('Base URL: '.$this->config['url']);
        $this->info('Installation path: '.getcwd());
    }
}
