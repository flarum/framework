<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends AbstractCommand
{
    public function __construct(
        protected ExtensionManager $manager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate:reset')
            ->setDescription('Run all migrations down for an extension')
            ->addOption(
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                'The extension to reset migrations for.'
            );
    }

    protected function fire(): int
    {
        $extensionName = $this->input->getOption('extension');

        if (! $extensionName) {
            $this->info('No extension specified. Please check command syntax.');

            return Command::INVALID;
        }

        $extension = $this->manager->getExtension($extensionName);

        if (! $extension) {
            $this->info('Could not find extension '.$extensionName);

            return Command::FAILURE;
        }

        $this->info('Rolling back extension: '.$extensionName);

        $this->manager->getMigrator()->setOutput($this->output);
        $this->manager->migrateDown($extension);

        $this->info('DONE.');

        return Command::SUCCESS;
    }
}
