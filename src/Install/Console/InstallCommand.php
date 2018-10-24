<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Exception;
use Flarum\Console\AbstractCommand;
use Flarum\Install\Installation;
use Flarum\Install\Pipeline;
use Flarum\Install\Step;
use Illuminate\Contracts\Validation\Factory;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends AbstractCommand
{
    /**
     * @var Installation
     */
    protected $installation;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var DataProviderInterface
     */
    protected $dataSource;

    /**
     * @param Installation $installation
     * @param Factory $validator
     */
    public function __construct(Installation $installation, Factory $validator)
    {
        $this->installation = $installation;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription("Run Flarum's installation migration and seeds")
            ->addOption(
                'defaults',
                'd',
                InputOption::VALUE_NONE,
                'Create default settings and user'
            )
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

        $prerequisites = $this->installation->prerequisites();
        $prerequisites->check();
        $errors = $prerequisites->getErrors();

        if (empty($errors)) {
            $this->info('Installing Flarum...');

            $this->install();

            $this->info('DONE.');
        } else {
            $this->output->writeln(
                '<error>Please fix the following errors before we can continue with the installation.</error>'
            );
            $this->showErrors($errors);
        }
    }

    protected function init()
    {
        if ($this->input->getOption('defaults')) {
            $this->dataSource = new DefaultsDataProvider();
        } elseif ($this->input->getOption('file')) {
            $this->dataSource = new FileDataProvider($this->input);
        } else {
            $this->dataSource = new UserDataProvider($this->input, $this->output, $this->getHelperSet()->get('question'));
        }
    }

    protected function install()
    {
        $dbConfig = $this->dataSource->getDatabaseConfiguration();

        $validation = $this->validator->make(
            $dbConfig,
            [
                'driver' => 'required|in:mysql',
                'host' => 'required',
                'database' => 'required|string',
                'username' => 'required|string',
                'prefix' => 'nullable|alpha_dash|max:10',
                'port' => 'nullable|integer|min:1|max:65535',
            ]
        );

        if ($validation->fails()) {
            throw new Exception(implode("\n",
                call_user_func_array('array_merge',
                    $validation->getMessageBag()->toArray())));
        }

        $admin = $this->dataSource->getAdminUser();

        if (strlen($admin['password']) < 8) {
            throw new Exception('Password must be at least 8 characters.');
        }

        if ($admin['password'] !== $admin['password_confirmation']) {
            throw new Exception('The password did not match its confirmation.');
        }

        if (! filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('You must enter a valid email.');
        }

        if (! $admin['username'] || preg_match('/[^a-z0-9_-]/i',
                $admin['username'])) {
            throw new Exception('Username can only contain letters, numbers, underscores, and dashes.');
        }

        $this->runPipeline(
            $this->installation
                ->configPath($this->input->getOption('config'))
                ->debugMode($this->dataSource->isDebugMode())
                ->baseUrl($this->dataSource->getBaseUrl())
                ->databaseConfig($dbConfig)
                ->adminUser($admin)
                ->settings($this->dataSource->getSettings())
                ->build()
        );
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

    protected function showErrors($errors)
    {
        foreach ($errors as $error) {
            $this->info($error['message']);

            if (isset($error['detail'])) {
                $this->output->writeln('<comment>'.$error['detail'].'</comment>');
            }
        }
    }
}
