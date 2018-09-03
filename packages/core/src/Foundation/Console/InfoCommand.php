<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

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
        $coreVersion = $this->findPackageVersion(__DIR__.'/../../../', Application::VERSION);
        $this->output->writeln("<info>Flarum core $coreVersion</info>");

        $this->output->writeln('<info>PHP version:</info> '.PHP_VERSION);

        $phpExtensions = implode(', ', get_loaded_extensions());
        $this->output->writeln("<info>Loaded extensions:</info> $phpExtensions");

        $table = new Table($this->output);
        $table->setHeaders([
            [
                'Flarum Extensions'
            ],
            [
                'ID',
                'Version',
                'Commit'
            ]
        ])->setStyle(
            (new TableStyle())
                ->setCellHeaderFormat('<info>%s</info>')
        );

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            /* @var \Flarum\Extension\Extension $extension */
            $name = $extension->getId();
            $fallback = $extension->getVersion();
            $version = $this->findPackageVersion($extension->getPath());

            $table->addRow([
                $name,
                $fallback,
                $version
            ]);
        }

        $table->render();

        $this->output->writeln('<info>Base URL:</info> '.$this->config['url']);
        $this->output->writeln('<info>Installation path:</info> '.getcwd());
        $this->output->writeln('<info>Debug mode:</info> '.($this->config['debug'] ? 'ON' : 'off'));

        if ($this->config['debug']) {
            $this->error(
                "Don't forget to turn off debug mode! It should never be turned on in a production system."
            );
        }
    }

    /**
     * Try to detect a package's exact version.
     *
     * If the package seems to be a Git version, we extract the currently
     * checked out commit using the command line.
     *
     * @param string $path
     * @param string $fallback
     * @return string
     */
    private function findPackageVersion($path, $fallback = null)
    {
        if (file_exists("$path/.git")) {
            $cwd = getcwd();
            chdir($path);

            $output = [];
            $status = null;
            exec('git rev-parse HEAD 2> /dev/null', $output, $status);

            chdir($cwd);

            if ($status == 0) {
                return isset($fallback) ? "$fallback ($output[0])" : $output[0];
            }
        }

        return $fallback;
    }
}
