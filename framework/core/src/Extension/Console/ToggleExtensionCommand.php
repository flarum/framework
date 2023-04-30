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

class ToggleExtensionCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensionManager;

    public function __construct(ExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extension:enable')
            ->setAliases(['extension:disable'])
            ->setDescription('Enable or disable an extension.')
            ->addArgument('extension-id', null, 'The ID of the extension to enable or disable.');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $name = $this->input->getArgument('extension-id');
        $enabling = $this->input->getFirstArgument() === 'extension:enable';

        if ($this->extensionManager->getExtension($name) === null) {
            $this->error("There are no extensions by the ID of '$name'.");

            return;
        }

        switch ($enabling) {
            case true:
                if ($this->extensionManager->isEnabled($name)) {
                    $this->info("The '$name' extension is already enabled.");

                    return;
                } else {
                    $this->info("Enabling '$name' extension...");
                    $this->extensionManager->enable($name);
                }
                break;
            case false:
                if (! $this->extensionManager->isEnabled($name)) {
                    $this->info("The '$name' extension is already disabled.");

                    return;
                } else {
                    $this->info("Disabling '$name' extension...");
                    $this->extensionManager->disable($name);
                }
                break;
        }
    }
}
