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

use Carbon\Carbon;
use Exception;
use Flarum\Console\AbstractCommand;
use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application as FlarumApplication;
use Flarum\Foundation\Site;
use Flarum\Group\Group;
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Validation\Factory;
use PDO;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends AbstractCommand
{
    /**
     * @var DataProviderInterface
     */
    protected $dataSource;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @param Application $application
     * @param Filesystem $filesystem
     */
    public function __construct(Application $application, Filesystem $filesystem)
    {
        $this->application = $application;

        parent::__construct();
        $this->filesystem = $filesystem;
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

        $prerequisites = $this->getPrerequisites();
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
        if ($this->dataSource === null) {
            if ($this->input->getOption('defaults')) {
                $this->dataSource = new DefaultsDataProvider();
            } elseif ($this->input->getOption('file')) {
                $this->dataSource = new FileDataProvider($this->input);
            } else {
                $this->dataSource = new UserDataProvider($this->input, $this->output, $this->getHelperSet()->get('question'));
            }
        }
    }

    public function setDataSource(DataProviderInterface $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    protected function install()
    {
        try {
            $this->dbConfig = $this->dataSource->getDatabaseConfiguration();

            $validation = $this->getValidator()->make(
                $this->dbConfig,
                [
                    'driver' => 'required|in:mysql',
                    'host' => 'required',
                    'database' => 'required|string',
                    'username' => 'required|string',
                    'prefix' => 'nullable|alpha_dash|max:10',
                    'port'   => 'nullable|integer|min:1|max:65535',
                ]
            );

            if ($validation->fails()) {
                throw new Exception(implode("\n", call_user_func_array('array_merge', $validation->getMessageBag()->toArray())));
            }

            $this->baseUrl = $this->dataSource->getBaseUrl();
            $this->settings = $this->dataSource->getSettings();
            $this->adminUser = $admin = $this->dataSource->getAdminUser();

            if (strlen($admin['password']) < 8) {
                throw new Exception('Password must be at least 8 characters.');
            }

            if ($admin['password'] !== $admin['password_confirmation']) {
                throw new Exception('The password did not match its confirmation.');
            }

            if (! filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('You must enter a valid email.');
            }

            if (! $admin['username'] || preg_match('/[^a-z0-9_-]/i', $admin['username'])) {
                throw new Exception('Username can only contain letters, numbers, underscores, and dashes.');
            }

            $this->storeConfiguration($this->dataSource->isDebugMode());

            $this->runMigrations();

            $this->writeSettings();

            $this->createAdminUser();

            $this->publishAssets();

            // Now that the installation of core is complete, boot up a new
            // application instance before enabling extensions so that all of
            // the application services are available.
            Site::fromPaths([
                'base' => $this->application->basePath(),
                'public' => $this->application->publicPath(),
                'storage' => $this->application->storagePath(),
            ])->bootApp();

            $this->application = FlarumApplication::getInstance();

            $this->enableBundledExtensions();
        } catch (Exception $e) {
            @unlink($this->getConfigFile());

            throw $e;
        }
    }

    protected function storeConfiguration(bool $debugMode)
    {
        $dbConfig = $this->dbConfig;

        $config = [
            'debug'    => $debugMode,
            'database' => $laravelDbConfig = [
                'driver'    => $dbConfig['driver'],
                'host'      => $dbConfig['host'],
                'database'  => $dbConfig['database'],
                'username'  => $dbConfig['username'],
                'password'  => $dbConfig['password'],
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => $dbConfig['prefix'],
                'port'      => $dbConfig['port'],
                'strict'    => false
            ],
            'url'   => $this->baseUrl,
            'paths' => [
                'api'   => 'api',
                'admin' => 'admin',
            ],
        ];

        $this->info('Testing config');

        $factory = new ConnectionFactory($this->application);

        $laravelDbConfig['engine'] = 'InnoDB';

        $this->db = $factory->make($laravelDbConfig);
        $version = $this->db->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        if (version_compare($version, '5.5.0', '<')) {
            throw new Exception('MySQL version too low. You need at least MySQL 5.5.');
        }

        $repository = new DatabaseMigrationRepository(
            $this->db, 'migrations'
        );
        $files = $this->application->make('files');

        $this->migrator = new Migrator($repository, $this->db, $files);

        $this->info('Writing config');

        file_put_contents(
            $this->getConfigFile(),
            '<?php return '.var_export($config, true).';'
        );
    }

    protected function runMigrations()
    {
        $this->migrator->setOutput($this->output);
        $this->migrator->getRepository()->createRepository();
        $this->migrator->run(__DIR__.'/../../../migrations');
    }

    protected function writeSettings()
    {
        $settings = new DatabaseSettingsRepository($this->db);

        $this->info('Writing default settings');

        $settings->set('version', $this->application->version());

        foreach ($this->settings as $k => $v) {
            $settings->set($k, $v);
        }
    }

    protected function createAdminUser()
    {
        $admin = $this->adminUser;

        if ($admin['password'] !== $admin['password_confirmation']) {
            throw new Exception('The password did not match its confirmation.');
        }

        $this->info('Creating admin user '.$admin['username']);

        $uid = $this->db->table('users')->insertGetId([
            'username' => $admin['username'],
            'email' => $admin['email'],
            'password' => (new BcryptHasher)->make($admin['password']),
            'joined_at' => Carbon::now(),
            'is_email_confirmed' => 1,
        ]);

        $this->db->table('group_user')->insert([
            'user_id' => $uid,
            'group_id' => Group::ADMINISTRATOR_ID,
        ]);
    }

    protected function enableBundledExtensions()
    {
        $extensions = new ExtensionManager(
            new DatabaseSettingsRepository($this->db),
            $this->application,
            $this->migrator,
            $this->application->make(Dispatcher::class),
            $this->application->make('files')
        );

        $disabled = [
            'flarum-akismet',
            'flarum-auth-facebook',
            'flarum-auth-github',
            'flarum-auth-twitter',
            'flarum-pusher',
        ];

        foreach ($extensions->getExtensions() as $name => $extension) {
            if (in_array($name, $disabled)) {
                continue;
            }

            $this->info('Enabling extension: '.$name);

            $extensions->enable($name);
        }
    }

    protected function publishAssets()
    {
        $this->filesystem->copyDirectory(
            $this->application->basePath().'/vendor/components/font-awesome/webfonts',
            $this->application->publicPath().'/assets/fonts'
        );
    }

    protected function getConfigFile()
    {
        return $this->input->getOption('config') ?: base_path('config.php');
    }

    /**
     * @return \Flarum\Install\Prerequisite\PrerequisiteInterface
     */
    protected function getPrerequisites()
    {
        return $this->application->make(PrerequisiteInterface::class);
    }

    /**
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidator()
    {
        return new Factory($this->application->make(Translator::class));
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
