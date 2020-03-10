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
use Symfony\Component\Console\Input\InputOption;

class ExtensionEnableAllCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param ExtensionManager $extensions
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:enableAll')
            ->setDescription("Enable all extensions.")
            ->addOption(
                'only-bundled',
                null,
                InputOption::VALUE_NONE,
                'Should only bundled extensions be enabled?'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        foreach ($this->extensions->getExtensions() as $extension) {
            if ($this->input->getOption('only-bundled') && substr($extension->getId(), 0, 6) ===  'flarum') {
                $this->info('Enabling: '.$extension->getId());
                $this->extensions->enable($extension->getId());
            } else {
                $this->info('Extension: '.$extension->getId().' is not bundled, ignoring');
            }
        }

        $this->info('DONE.');
    }
}
