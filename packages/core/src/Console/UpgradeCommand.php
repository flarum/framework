<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends Command
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('upgrade')
            ->setDescription("Run Flarum's upgrade script");
    }

    /**
     * @inheritdoc
     */
    protected function fire()
    {
        $this->info('Upgrading Flarum...');

        $this->upgrade();

        $this->info('DONE.');
    }

    protected function upgrade()
    {
        $this->container->bind('Illuminate\Database\Schema\Builder', function ($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->container->make('Flarum\Migrations\Migrator');

        $migrator->run(base_path('core/migrations'));

        foreach ($migrator->getNotes() as $note) {
            $this->info($note);
        }

        $extensions = $this->container->make('Flarum\Support\ExtensionManager');

        $migrator = $extensions->getMigrator();

        foreach ($extensions->getInfo() as $extension) {
            if (! $extensions->isEnabled($extension->name)) {
                continue;
            }

            $this->info('Upgrading extension: '.$extension->name);

            $extensions->migrate($extension->name);

            foreach ($migrator->getNotes() as $note) {
                $this->info($note);
            }
        }
    }
}
