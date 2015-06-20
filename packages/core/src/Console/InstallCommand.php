<?php namespace Flarum\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Flarum\'s installation migrations and seeds.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $path = str_replace($this->laravel['path.base'].'/', '', __DIR__.'/../../migrations');

        $this->call('migrate', ['--path' => $path]);

        $this->call('db:seed', ['--class' => 'Flarum\Core\Seeders\ConfigTableSeeder']);
        $this->call('db:seed', ['--class' => 'Flarum\Core\Seeders\GroupsTableSeeder']);
        $this->call('db:seed', ['--class' => 'Flarum\Core\Seeders\PermissionsTableSeeder']);

        // Create config file so that we know Flarum is installed
        copy(base_path('../config.example.php'), base_path('../config.php'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            // ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            // ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
