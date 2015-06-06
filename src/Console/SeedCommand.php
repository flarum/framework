<?php namespace Flarum\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SeedCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'flarum:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Flarum\'s database with fake data.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->call('db:seed', ['--class' => 'Flarum\Core\Seeders\UsersTableSeeder']);
        $this->call('db:seed', ['--class' => 'Flarum\Core\Seeders\DiscussionsTableSeeder']);
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
