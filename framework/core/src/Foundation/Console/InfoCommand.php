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

        $cliPhpVersion = $this->appInfo->identifyPHPVersion();
        $webPhpVersion = $this->detectWebServerPhpVersion();

        if ($webPhpVersion) {
            if ($webPhpVersion !== $cliPhpVersion) {
                $this->output->writeln("<info>PHP version:</info> <error>CLI: {$cliPhpVersion}, Web: {$webPhpVersion} (MISMATCH - versions should match!)</error>");
            } else {
                $this->output->writeln("<info>PHP version:</info> CLI: {$cliPhpVersion}, Web: {$webPhpVersion}");
            }
        } else {
            $this->output->writeln("<info>PHP version:</info> CLI: {$cliPhpVersion}, Web: unable to detect");
        }

        $cliMemoryLimit = ini_get('memory_limit');
        $webMemoryLimit = $this->detectWebServerMemoryLimit();

        if ($webMemoryLimit) {
            $this->output->writeln("<info>PHP memory limit:</info> CLI: {$cliMemoryLimit}, Web: {$webMemoryLimit}");
        } else {
            $this->output->writeln("<info>PHP memory limit:</info> CLI: {$cliMemoryLimit}, Web: unable to detect");
        }

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
                ['ID', 'Version', 'Commit', 'Notes']
            ])->setStyle(
                (new TableStyle)->setCellHeaderFormat('<info>%s</info>')
            );

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            $abandoned = $extension->getAbandoned();
            $notesText = '';

            if ($abandoned) {
                // Package is abandoned, show replacement info or deprecation notice
                if (is_string($abandoned)) {
                    // Replacement exists - highlight in red (more urgent)
                    $notesText = "Replaced by {$abandoned}";
                    $table->addRow([
                        "<error>{$extension->getId()}</error>",
                        "<error>{$extension->getVersion()}</error>",
                        "<error>{$this->findPackageVersion($extension->getPath())}</error>",
                        "<error>{$notesText}</error>"
                    ]);
                } else {
                    // Just deprecated - highlight in yellow (warning)
                    $notesText = 'Deprecated';
                    $table->addRow([
                        "<comment>{$extension->getId()}</comment>",
                        "<comment>{$extension->getVersion()}</comment>",
                        "<comment>{$this->findPackageVersion($extension->getPath())}</comment>",
                        "<comment>{$notesText}</comment>"
                    ]);
                }
            } else {
                $table->addRow([
                    $extension->getId(),
                    $extension->getVersion(),
                    $this->findPackageVersion($extension->getPath()),
                    $notesText
                ]);
            }
        }

        return $table;
    }

    /**
     * Try to detect the web server's PHP version.
     * This is a best-effort attempt and may not work in all environments.
     */
    private function detectWebServerPhpVersion(): ?string
    {
        // Try common PHP binary paths for web servers
        $possiblePhpBinaries = [
            '/usr/bin/php-fpm' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            '/usr/sbin/php-fpm' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            '/usr/local/bin/php',
            '/usr/bin/php',
        ];

        foreach ($possiblePhpBinaries as $phpBinary) {
            if (@file_exists($phpBinary) && @is_executable($phpBinary)) {
                $output = [];
                $status = null;
                exec("$phpBinary -v 2>&1 | head -n 1", $output, $status);

                if ($status === 0 && !empty($output[0])) {
                    // Extract version from output like "PHP 8.3.1 (fpm-fcgi) ..."
                    if (preg_match('/PHP\s+([\d.]+)/', $output[0], $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Try to detect the web server's PHP memory limit.
     * This is a best-effort attempt and may not work in all environments.
     */
    private function detectWebServerMemoryLimit(): ?string
    {
        // Try to detect PHP-FPM pool configuration
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $possiblePaths = [
            // Docker PHP paths (most common for containerized setups)
            "/usr/local/etc/php/php.ini",
            "/usr/local/etc/php/conf.d/memory.ini",
            // Common PHP-FPM pool paths
            "/etc/php/{$phpVersion}/fpm/pool.d/www.conf",
            "/etc/php{$phpVersion}/fpm/pool.d/www.conf",
            "/etc/php-fpm.d/www.conf",
            "/usr/local/etc/php-fpm.d/www.conf",
            // Common php.ini paths for web
            "/etc/php/{$phpVersion}/fpm/php.ini",
            "/etc/php{$phpVersion}/fpm/php.ini",
            "/etc/php.ini",
        ];

        foreach ($possiblePaths as $path) {
            if (@file_exists($path) && @is_readable($path)) {
                $content = @file_get_contents($path);

                // Look for memory_limit setting
                if ($content && preg_match('/^\s*memory_limit\s*=\s*(.+?)\s*$/m', $content, $matches)) {
                    return trim($matches[1]);
                }

                // For FPM pool configs, also check php_admin_value
                if ($content && preg_match('/^\s*php_admin_value\[memory_limit\]\s*=\s*(.+?)\s*$/m', $content, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        // Also scan /usr/local/etc/php/conf.d/ directory for any INI files with memory_limit
        $confDir = '/usr/local/etc/php/conf.d';
        if (@is_dir($confDir) && @is_readable($confDir)) {
            $iniFiles = @glob($confDir . '/*.ini');
            if ($iniFiles) {
                foreach ($iniFiles as $iniFile) {
                    if (@is_readable($iniFile)) {
                        $content = @file_get_contents($iniFile);
                        if ($content && preg_match('/^\s*memory_limit\s*=\s*(.+?)\s*$/m', $content, $matches)) {
                            return trim($matches[1]);
                        }
                    }
                }
            }
        }

        return null;
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
