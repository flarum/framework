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

class ExtensionDisableAllCommand extends AbstractCommand
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
            ->setName('extensions:disableAll')
            ->setDescription("Disable all extensions.")
            ->addOption(
                'keep-bundled',
                null,
                InputOption::VALUE_NONE,
                'Should bundled extensions be kept enabled?'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            if ($this->input->getOption('keep-bundled') && substr($extension->getId(), 0, 6) ===  'flarum') {
                $this->info('Extension: '.$extension->getId().' is bundled, ignoring');
            } else {
                $this->info('Disabling: ' . $extension->getId());

                $this->extensions->disable($extension->getId());
            }
        }

        $this->info('DONE.');
    }
}
