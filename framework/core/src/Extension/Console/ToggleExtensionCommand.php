<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Symfony\Component\Console\Command\Command;

class ToggleExtensionCommand extends AbstractCommand
{
    public function __construct(
        protected ExtensionManager $extensionManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('extension:enable')
            ->setAliases(['extension:disable'])
            ->setDescription('Enable or disable an extension.')
            ->addArgument('extension-id', null, 'The ID of the extension to enable or disable.');
    }

    protected function fire(): int
    {
        $name = $this->input->getArgument('extension-id');
        $enabling = $this->input->getFirstArgument() === 'extension:enable';

        if ($this->extensionManager->getExtension($name) === null) {
            $this->error("There are no extensions by the ID of '$name'.");

            return Command::INVALID;
        }

        if ($enabling) {
            if ($this->extensionManager->isEnabled($name)) {
                $this->info("The '$name' extension is already enabled.");

                return Command::FAILURE;
            }

            $this->info("Enabling '$name' extension...");
            $this->extensionManager->enable($name);
        } else {
            if (! $this->extensionManager->isEnabled($name)) {
                $this->info("The '$name' extension is already disabled.");

                return Command::FAILURE;
            }

            $this->info("Disabling '$name' extension...");
            $this->extensionManager->disable($name);
        }

        return Command::SUCCESS;
    }
}
