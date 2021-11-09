<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

use Composer\Console\Application;
use Flarum\PackageManager\OutputLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

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

    public function __construct(Application $application, OutputLogger $logger)
    {
        $this->application = $application;
        $this->logger = $logger;
    }

    public function run(InputInterface $input): ComposerOutput
    {
        $output = new BufferedOutput();

        $exitCode = $this->application->run($input, $output);

        $outputContents = $output->fetch();

        $this->logger->log($input->__toString(), $outputContents, $exitCode);

        return new ComposerOutput($exitCode, $outputContents);
    }
}
