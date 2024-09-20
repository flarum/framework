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
        private readonly Paths $paths
    ) {
    }

    public function run(InputInterface $input, ?Task $task = null): ComposerOutput
    {
        $this->application->resetComposer();

        $this->output ??= new BufferedOutput();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);
        $exitCode = $this->application->run($input, $this->output);
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
