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
use Flarum\PackageManager\Support\Util;
use Flarum\PackageManager\Task\Task;
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

    public function __construct(Application $application, OutputLogger $logger, Paths $paths)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->paths = $paths;
    }

    public function run(InputInterface $input, ?Task $task = null): ComposerOutput
    {
        $this->application->resetComposer();

        $output = new BufferedOutput();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);
        $exitCode = $this->application->run($input, $output);
        chdir($currDir);

        $command = Util::readableConsoleInput($input);
        $output = $output->fetch();

        if ($task) {
            $task->update(compact('command', 'output'));
        } else {
            $this->logger->log($command, $output, $exitCode);
        }

        return new ComposerOutput($exitCode, $output);
    }

    public static function setPhpVersion(string $phpVersion)
    {
        Config::$defaultConfig['platform']['php'] = $phpVersion;
    }
}
