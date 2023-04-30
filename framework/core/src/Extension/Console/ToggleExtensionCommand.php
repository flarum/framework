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
            ->setName('extension:toggle')
            ->setDescription('Enable or disable an extension.')
            ->addArgument('extension', null, 'The extension to enable or disable.')
            ->addOption('enable', null, null, 'Enable the extension.')
            ->addOption('disable', null, null, 'Disable the extension.');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $name = $this->input->getArgument('extension');

        if ($this->input->getOption('enable')) {
            $this->info("Enabling $name extension...");
            $this->extensionManager->enable($name);
        } elseif ($this->input->getOption('disable')) {
            $this->info("Disabling $name extension...");
            $this->extensionManager->disable($name);
        } else {
            $this->error('You must specify --enable or --disable.');
        }
    }
}
