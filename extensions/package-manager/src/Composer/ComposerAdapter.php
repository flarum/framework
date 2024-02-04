<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Composer;

use Composer\Config;
use Composer\Console\Application;
use Flarum\Foundation\Paths;
use Flarum\ExtensionManager\OutputLogger;
use Flarum\ExtensionManager\Support\Util;
use Flarum\ExtensionManager\Task\Task;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
class ComposerAdapter
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var OutputLogger
     */
    private $logger;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var BufferedOutput|null
     */
    private $output = null;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Application $application, OutputLogger $logger, Paths $paths, Filesystem $filesystem)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    public function run(InputInterface $input, ?Task $task = null, bool $safeMode = false): ComposerOutput
    {
        $this->application->resetComposer();

        $this->output = $this->output ?? new BufferedOutput();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);

        if ($safeMode) {
            $temporaryVendorDir = $this->paths->base . DIRECTORY_SEPARATOR . 'temp-vendor';
            if (! $this->filesystem->isDirectory($temporaryVendorDir)) {
                $this->filesystem->makeDirectory($temporaryVendorDir);
            }
            Config::$defaultConfig['vendor-dir'] = $temporaryVendorDir;
        }

        $exitCode = $this->application->run($input, $this->output);

        if ($safeMode) {
            // Move the temporary vendor directory to the real vendor directory.
            if ($this->filesystem->isDirectory($temporaryVendorDir) && count($this->filesystem->allFiles($temporaryVendorDir))) {
                $vendorDir = $this->paths->vendor;
                if (file_exists($vendorDir)) {
                    $this->filesystem->deleteDirectory($vendorDir);
                }
                $this->filesystem->moveDirectory($temporaryVendorDir, $vendorDir);
            }
            Config::$defaultConfig['vendor-dir'] = $this->paths->vendor;
        }

        chdir($currDir);

        $command = Util::readableConsoleInput($input);
        $outputContent = $this->output->fetch();

        if ($task) {
            $task->update([
                'command' => $command,
                'output' => $outputContent,
            ]);
        } else {
            $this->logger->log($command, $outputContent, $exitCode);
        }

        return new ComposerOutput($exitCode, $outputContent);
    }

    public static function setPhpVersion(string $phpVersion)
    {
        Config::$defaultConfig['platform']['php'] = $phpVersion;
    }
}
