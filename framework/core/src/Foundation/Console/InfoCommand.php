<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class InfoCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var ApplicationInfoProvider
     */
    private $appInfo;

    public function __construct(
        ExtensionManager $extensions,
        Config $config,
        SettingsRepositoryInterface $settings,
        ConnectionInterface $db,
        ApplicationInfoProvider $appInfo
    ) {
        $this->extensions = $extensions;
        $this->config = $config;
        $this->settings = $settings;
        $this->db = $db;
        $this->appInfo = $appInfo;

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
        $this->output->writeln("<info>Flarum core:</info> $coreVersion");

        $this->output->writeln('<info>PHP version:</info> '.$this->appInfo->identifyPHPVersion());
        $this->output->writeln('<info>MySQL version:</info> '.$this->appInfo->identifyDatabaseVersion());

        $phpExtensions = implode(', ', get_loaded_extensions());
        $this->output->writeln("<info>Loaded extensions:</info> $phpExtensions");

        $this->getExtensionTable()->render();

        $this->output->writeln('<info>Base URL:</info> '.$this->config->url());
        $this->output->writeln('<info>Installation path:</info> '.getcwd());
        $this->output->writeln('<info>Queue driver:</info> '.$this->appInfo->identifyQueueDriver());
        $this->output->writeln('<info>Session driver:</info> '.$this->appInfo->identifySessionDriver());

        if ($this->appInfo->scheduledTasksRegistered()) {
            $this->output->writeln('<info>Scheduler status:</info> '.$this->appInfo->getSchedulerStatus());
        }

        $this->output->writeln('<info>Mail driver:</info> '.$this->settings->get('mail_driver', 'unknown'));
        $this->output->writeln('<info>Debug mode:</info> '.($this->config->inDebugMode() ? '<error>ON</error>' : 'off'));

        if ($this->config->inDebugMode()) {
            $this->output->writeln('');
            $this->error(
                "Don't forget to turn off debug mode! It should never be turned on in a production system."
            );
        }
    }

    private function getExtensionTable()
    {
        $table = (new Table($this->output))
            ->setHeaders([
                ['Flarum Extensions'],
                ['ID', 'Version', 'Commit']
            ])->setStyle(
                (new TableStyle)->setCellHeaderFormat('<info>%s</info>')
            );

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            $table->addRow([
                $extension->getId(),
                $extension->getVersion(),
                $this->findPackageVersion($extension->getPath())
            ]);
        }

        return $table;
    }

    /**
     * Try to detect a package's exact version.
     *
     * If the package seems to be a Git version, we extract the currently
     * checked out commit using the command line.
     */
    private function findPackageVersion(string $path, string $fallback = null): ?string
    {
        if (file_exists("$path/.git")) {
            $cwd = getcwd();
            chdir($path);

            $output = [];
            $status = null;
            exec('git rev-parse HEAD 2>&1', $output, $status);

            chdir($cwd);

            if ($status == 0) {
                return isset($fallback) ? "$fallback ($output[0])" : $output[0];
            }
        }

        return $fallback;
    }
}
