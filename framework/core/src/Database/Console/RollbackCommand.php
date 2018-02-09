<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;

class RollbackCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $manager;

    /**
     * @param ExtensionManager $manager
     */
    public function __construct(ExtensionManager $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Run rollback migrations for an extension')
            ->addArgument(
                'extension',
                InputArgument::REQUIRED,
                'The name of the extension.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $extensionName = $this->input->getArgument('extension');
        $extension = $this->manager->getExtension($extensionName);

        if (!$extension) {
            $this->info('Could not find extension ' . $extensionName);

            return;
        }

        $this->info('Rolling back extension: ' . $extensionName);

        $notes = $this->manager->migrateDown($extension);

        foreach ($notes as $note) {
            $this->info($note);
        }

        $this->info('DONE.');
    }
}
