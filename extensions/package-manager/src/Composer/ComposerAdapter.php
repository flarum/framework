<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

use Composer\Config;
use Composer\Console\Application;
use Flarum\Foundation\Paths;
use Flarum\PackageManager\OutputLogger;
use Flarum\PackageManager\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
class ComposerAdapter
{
    public function __construct(
        private readonly Application $application,
        private readonly OutputLogger $logger,
        private readonly Paths $paths,
        private readonly BufferedOutput $output
    ) {}

    public function run(InputInterface $input, ?Task $task = null): ComposerOutput
    {
        $this->application->resetComposer();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);
        $exitCode = $this->application->run($input, $this->output);
        chdir($currDir);

        // @phpstan-ignore-next-line
        $command = $input->__toString();
        $output = $this->output->fetch();

        if ($task) {
            $task->update(compact('command', 'output'));
        } else {
            $this->logger->log($command, $output, $exitCode);
        }

        return new ComposerOutput($exitCode, $output);
    }

    public static function setPhpVersion(string $phpVersion): void
    {
        Config::$defaultConfig['platform']['php'] = $phpVersion;
    }
}
