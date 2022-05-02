<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

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
    /**
     * @var Application
     */
    private $application;

    /**
     * @var OutputLogger
     */
    private $logger;

    /**
     * @var BufferedOutput
     */
    private $output;

    /**
     * @var Paths
     */
    private $paths;

    public function __construct(Application $application, OutputLogger $logger, Paths $paths)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->paths = $paths;
        $this->output = new BufferedOutput();
    }

    public function run(InputInterface $input, ?Task $task = null): ComposerOutput
    {
        $this->application->resetComposer();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);
        $exitCode = $this->application->run($input, $this->output);
        chdir($currDir);

        $command = $input->__toString();
        $output = $this->output->fetch();

        if ($task) {
            $task->update(compact('command', 'output'));
        } else {
            $this->logger->log($command, $output, $exitCode);
        }

        return new ComposerOutput($exitCode, $output);
    }
}
