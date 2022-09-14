<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Install\Installation;
use Flarum\Install\Pipeline;
use Flarum\Install\Step;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends AbstractCommand
{
    /**
     * @var Installation
     */
    protected $installation;

    /**
     * @var DataProviderInterface
     */
    protected $dataSource;

    /**
     * @param Installation $installation
     */
    public function __construct(Installation $installation)
    {
        $this->installation = $installation;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription("Run Flarum's installation migration and seeds")
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Use external configuration file in JSON or YAML format'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Set the path to write the config file to'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->init();

        $problems = $this->installation->prerequisites()->problems();

        if ($problems->isEmpty()) {
            $this->info('Installing Flarum...');

            $this->install();

            $this->info('DONE.');
        } else {
            $this->showProblems($problems);

            return 1;
        }
    }

    protected function init()
    {
        if ($this->input->getOption('file')) {
            $this->dataSource = new FileDataProvider($this->input);
        } else {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelperSet()->get('question');
            $this->dataSource = new UserDataProvider($this->input, $this->output, $questionHelper);
        }
    }

    protected function install()
    {
        $pipeline = $this->dataSource->configure(
            $this->installation->configPath($this->input->getOption('config'))
        )->build();

        $this->runPipeline($pipeline);
    }

    private function runPipeline(Pipeline $pipeline)
    {
        $pipeline
            ->on('start', function (Step $step) {
                $this->output->write($step->getMessage().'...');
            })->on('end', function () {
                $this->output->write("<info>done</info>\n");
            })->on('fail', function () {
                $this->output->write("<error>failed</error>\n");
                $this->output->writeln('Rolling back...');
            })->on('rollback', function (Step $step) {
                $this->output->writeln($step->getMessage().' (rollback)');
            })
            ->run();
    }

    protected function showProblems($problems)
    {
        $this->output->writeln(
            '<error>Please fix the following problems before we can continue with the installation.</error>'
        );

        foreach ($problems as $problem) {
            $this->info($problem['message']);

            if (isset($problem['detail'])) {
                $this->output->writeln('<comment>'.$problem['detail'].'</comment>');
            }
        }
    }
}
