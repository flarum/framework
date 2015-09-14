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

use Flarum\Console\Command;
use Flarum\Core\Exceptions\ValidationException;
use Flarum\Core\Model;
use Flarum\Core\Users\User;
use Flarum\Core\Groups\Group;
use Flarum\Core\Groups\Permission;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class InstallCommand extends Command
{
    /**
     * @var ProvidesData
     */
    protected $dataSource;

    /**
     * @var Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription("Run Flarum's installation migration and seeds.")
            ->addOption(
                'defaults',
                'd',
                InputOption::VALUE_NONE,
                'Create default settings and user'
            );
    }

    /**
     * @inheritdoc
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
                $this->dataSource = new DefaultData();
            } else {
                $this->dataSource = new DataFromUser($this->input, $this->output, $this->getHelperSet()->get('question'));
            }
        }
    }

    public function setDataSource(ProvidesData $dataSource)
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
                    'database' => 'required|alpha_dash',
                    'username' => 'required|alpha_dash',
                    'prefix' => 'alpha_dash|max:10'
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

            $this->storeConfiguration();

            $this->runMigrations();

            $this->writeSettings();

            $this->application->register('Flarum\Core\CoreServiceProvider');

            $resolver = $this->application->make('Illuminate\Database\ConnectionResolverInterface');
            Model::setConnectionResolver($resolver);
            Model::setEventDispatcher($this->application->make('events'));

            $this->seedGroups();
            $this->seedPermissions();

            $this->createAdminUser();

            $this->enableBundledExtensions();
        } catch (Exception $e) {
            @unlink($this->getConfigFile());

            throw $e;
        }
    }

    protected function storeConfiguration()
    {
        $dbConfig = $this->dbConfig;

        $config = [
            'debug'    => true,
            'database' => [
                'driver'    => $dbConfig['driver'],
                'host'      => $dbConfig['host'],
                'database'  => $dbConfig['database'],
                'username'  => $dbConfig['username'],
                'password'  => $dbConfig['password'],
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => $dbConfig['prefix'],
                'strict'    => false
            ],
            'url'   => $this->baseUrl,
            'paths' => [
                'api'   => 'api',
                'admin' => 'admin',
            ],
        ];

        $this->info('Testing config');

        $this->application->instance('flarum.config', $config);
        /* @var $db \Illuminate\Database\ConnectionInterface */
        $db = $this->application->make('flarum.db');
        $version = $db->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        if (version_compare($version, '5.5.0', '<')) {
            throw new Exception('MySQL version too low. You need at least MySQL 5.5.');
        }

        $this->info('Writing config');

        file_put_contents(
            $this->getConfigFile(),
            '<?php return '.var_export($config, true).';'
        );
    }

    protected function runMigrations()
    {
        $this->application->bind('Illuminate\Database\Schema\Builder', function ($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->application->make('Flarum\Migrations\Migrator');
        $migrator->getRepository()->createRepository();

        $migrator->run(__DIR__ . '/../../../migrations');

        foreach ($migrator->getNotes() as $note) {
            $this->info($note);
        }
    }

    protected function writeSettings()
    {
        $settings = $this->application->make('Flarum\Core\Settings\SettingsRepository');

        $this->info('Writing default settings');

        foreach ($this->settings as $k => $v) {
            $settings->set($k, $v);
        }
    }

    protected function seedGroups()
    {
        Group::unguard();

        $groups = [
            ['Admin', 'Admins', '#B72A2A', 'wrench'],
            ['Guest', 'Guests', null, null],
            ['Member', 'Members', null, null],
            ['Mod', 'Mods', '#80349E', 'bolt']
        ];

        foreach ($groups as $group) {
            Group::create([
                'name_singular' => $group[0],
                'name_plural' => $group[1],
                'color' => $group[2],
                'icon' => $group[3]
            ]);
        }
    }

    protected function seedPermissions()
    {
        $permissions = [
            // Guests can view the forum
            [2, 'forum.view'],

            // Members can create and reply to discussions
            [3, 'forum.startDiscussion'],
            [3, 'discussion.reply'],

            // Moderators can edit + delete stuff
            [4, 'discussion.delete'],
            [4, 'discussion.deletePosts'],
            [4, 'discussion.editPosts'],
            [4, 'discussion.rename'],
        ];

        foreach ($permissions as &$permission) {
            $permission = [
                'group_id'   => $permission[0],
                'permission' => $permission[1]
            ];
        }

        Permission::insert($permissions);
    }

    protected function createAdminUser()
    {
        $admin = $this->adminUser;

        if ($admin['password'] !== $admin['password_confirmation']) {
            throw new Exception('The password did not match its confirmation.');
        }

        $this->info('Creating admin user '.$admin['username']);

        $user = User::register(
            $admin['username'],
            $admin['email'],
            $admin['password']
        );

        $user->is_activated = 1;
        $user->save();

        $user->groups()->sync([1]);
    }

    protected function enableBundledExtensions()
    {
        $extensions = $this->application->make('Flarum\Support\ExtensionManager');

        $migrator = $extensions->getMigrator();

        foreach ($extensions->getInfo() as $extension) {
            $name = $extension->name;

            if ($name === 'pusher') {
                continue;
            }

            $this->info('Enabling extension: '.$name);

            $extensions->enable($name);

            foreach ($migrator->getNotes() as $note) {
                $this->info($note);
            }
        }
    }

    protected function getConfigFile()
    {
        return base_path('../config.php');
    }

    /**
     * @return \Flarum\Install\Prerequisites\Prerequisite
     */
    protected function getPrerequisites()
    {
        return $this->application->make('Flarum\Install\Prerequisites\Prerequisite');
    }

    /**
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidator()
    {
        return new Factory($this->application->make('Symfony\Component\Translation\TranslatorInterface'));
    }

    protected function showErrors($errors)
    {
        foreach ($errors as $error) {
            $this->info($error['message']);

            if (isset($error['detail'])) {
                $this->output->writeln('<comment>' . $error['detail'] . '</comment>');
            }
        }
    }
}
