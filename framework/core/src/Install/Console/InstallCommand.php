<?php namespace Flarum\Install\Console;

use Flarum\Console\Command;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    /**
     * @var ProvidesData
     */
    protected $dataSource;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->container->bind('Illuminate\Database\Schema\Builder', function($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

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

        $this->info('Installing Flarum...');

        $this->install();

        $this->info('DONE.');
    }

    protected function init()
    {
        if ($this->input->getOption('defaults')) {
            $this->dataSource = new DefaultData();
        } else {
            $this->dataSource = new DataFromUser($this->input, $this->output, $this->getHelperSet()->get('question'));
        }
    }

    protected function install()
    {
        $this->storeConfiguration();

        $this->runMigrations();

        $this->createAdminUser();
    }

    protected function storeConfiguration()
    {
        $dbConfig = $this->dataSource->getDatabaseConfiguration();

        $config = [
            'debug'    => true,
            'database' => [
                'driver'    => $dbConfig['driver'],
                'host'      => $dbConfig['host'],
                'database'  => $dbConfig['database'],
                'username'  => $dbConfig['username'],
                'password'  => $dbConfig['password'],
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => $dbConfig['prefix'],
                'strict'    => false
            ],
        ];

        $this->info('Writing config');

        $this->container->instance('flarum.config', $config);
        file_put_contents(
            base_path('../config.php'),
            '<?php return '.var_export($config, true).';'
        );
    }

    protected function runMigrations()
    {
        $migrationDir = base_path('core/migrations');

        $files = glob("$migrationDir/*_*.php") or [];
        sort($files);

        foreach ($files as $file) {
            require $file;

            $migrationClass = studly_case(substr(basename($file), 18));
            $migrationClass = str_replace('.php', '', $migrationClass);
            $migration = $this->container->make($migrationClass);

            $this->info("Migrating $migrationClass");

            $migration->up();
        }
    }

    protected function createAdminUser()
    {
        $admin = $this->dataSource->getAdminUser();
        $db = $this->getDatabaseConnection();

        $this->info('Creating admin user '.$admin['username']);

        $db->table('users')->insert($admin);
    }

    /**
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function getDatabaseConnection()
    {
        return $this->container->make('Illuminate\Database\ConnectionInterface');
    }
}
