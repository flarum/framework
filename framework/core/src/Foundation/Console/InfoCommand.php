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
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\SessionManager;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PDO;
use SessionHandlerInterface;
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
     * @var Queue
     */
    private $queue;

    /**
     * @var SessionManager
     */
    private $session;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    public function __construct(
        ExtensionManager $extensions,
        Config $config,
        SettingsRepositoryInterface $settings,
        ConnectionInterface $db,
        Queue $queue,
        SessionManager $session,
        SessionHandlerInterface $sessionHandler
    ) {
        $this->extensions = $extensions;
        $this->config = $config;
        $this->settings = $settings;
        $this->db = $db;
        $this->queue = $queue;
        $this->session = $session;
        $this->sessionHandler = $sessionHandler;

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
        $this->output->writeln('<info>MySQL version:</info> '.$this->identifyDatabaseVersion());

        $phpExtensions = implode(', ', get_loaded_extensions());
        $this->output->writeln("<info>Loaded extensions:</info> $phpExtensions");

        $this->getExtensionTable()->render();

        $this->output->writeln('<info>Base URL:</info> '.$this->config->url());
        $this->output->writeln('<info>Installation path:</info> '.getcwd());
        $this->output->writeln('<info>Queue driver:</info> '.$this->identifyQueueDriver());
        $this->output->writeln('<info>Session driver:</info> '.$this->identifySessionDriver());
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

    private function identifyQueueDriver(): string
    {
        // Get class name
        $queue = get_class($this->queue);
        // Drop the namespace
        $queue = Str::afterLast($queue, '\\');
        // Lowercase the class name
        $queue = strtolower($queue);
        // Drop everything like queue SyncQueue, RedisQueue
        $queue = str_replace('queue', '', $queue);

        return $queue;
    }

    private function identifyDatabaseVersion(): string
    {
        return $this->db->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Reports on the session driver in use based on three scenarios:
     *  1. If the configured session driver is valid and in use, it will be returned.
     *  2. If the configured session driver is invalid, fallback to the default one and mention it.
     *  3. If the actual used driver (i.e `session.handler`) is different from the current one (configured or default), mention it.
     */
    private function identifySessionDriver(): string
    {
        /*
         * Get the configured driver and fallback to the default one.
         */
        $defaultDriver = $this->session->getDefaultDriver();
        $configuredDriver = Arr::get($this->config, 'session.driver', $defaultDriver);
        $driver = $configuredDriver;

        try {
            // Try to get the configured driver instance.
            // Driver instances are created on demand.
            $this->session->driver($configuredDriver);
        } catch (InvalidArgumentException $e) {
            // An exception is thrown if the configured driver is not a valid driver.
            // So we fallback to the default driver.
            $driver = $defaultDriver;
        }

        /*
         * Get actual driver name from its class name.
         * And compare that to the current configured driver.
         */
        // Get class name
        $handlerName = get_class($this->sessionHandler);
        // Drop the namespace
        $handlerName = Str::afterLast($handlerName, '\\');
        // Lowercase the class name
        $handlerName = strtolower($handlerName);
        // Drop everything like sessionhandler FileSessionHandler, DatabaseSessionHandler ..etc
        $handlerName = str_replace('sessionhandler', '', $handlerName);

        if ($driver !== $handlerName) {
            return "$handlerName <comment>(Code override. Configured to <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        if ($driver !== $configuredDriver) {
            return "$driver <comment>(Fallback default driver. Configured to invalid driver <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        return $driver;
    }
}
