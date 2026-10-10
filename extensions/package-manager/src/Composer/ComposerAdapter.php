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
use Flarum\ExtensionManager\OutputLogger;
use Flarum\ExtensionManager\Support\Util;
use Flarum\ExtensionManager\Task\Task;
use Flarum\Foundation\Paths;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
class ComposerAdapter
{
    private BufferedOutput $output;

    public function __construct(
        private readonly Application $application,
        private readonly OutputLogger $logger,
        private readonly Paths $paths,
        private readonly Filesystem $filesystem
    ) {
    }

    public function run(InputInterface $input, ?Task $task = null, bool $safeMode = false): ComposerOutput
    {
        $this->application->resetComposer();

        $this->output ??= new BufferedOutput();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);

        if ($safeMode) {
            $temporaryVendorDir = $this->paths->base.DIRECTORY_SEPARATOR.'temp-vendor';
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
                $previousVendorDir = $vendorDir.'-previous';

                // Left over from a run that was interrupted before it could clean
                // up after itself.
                if ($this->filesystem->isDirectory($previousVendorDir)) {
                    $this->filesystem->deleteDirectory($previousVendorDir);
                }

                // Two renames, rather than deleting the live vendor directory and
                // then moving the new one into place. Deleting first leaves the
                // forum with no vendor directory for as long as it takes to remove
                // and then move several hundred megabytes of packages, and this
                // runs at the very end of a long job that can be killed by a
                // worker timeout, a memory limit or a container restart. A process
                // that dies inside that window leaves the site with no vendor
                // directory at all, and a temp-vendor nobody thinks to look for.
                // Renaming is effectively instantaneous on the same filesystem,
                // and keeps the working tree until the new one is in place.
                if ($this->filesystem->isDirectory($vendorDir)) {
                    $this->filesystem->moveDirectory($vendorDir, $previousVendorDir);
                }

                if (! $this->filesystem->moveDirectory($temporaryVendorDir, $vendorDir)) {
                    // Put the working tree back rather than leave the site without
                    // one. The caller records this on the task, so the admin sees a
                    // failed update instead of a forum that has stopped booting.
                    $this->filesystem->moveDirectory($previousVendorDir, $vendorDir);

                    throw new \RuntimeException('Failed to move the new vendor directory into place.');
                }

                $this->filesystem->deleteDirectory($previousVendorDir);
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

    public static function setPhpVersion(string $phpVersion): void
    {
        Config::$defaultConfig['platform']['php'] = $phpVersion;
    }
}
