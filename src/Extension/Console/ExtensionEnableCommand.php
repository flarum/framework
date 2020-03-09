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
use Symfony\Component\Console\Input\InputArgument;

class ExtensionEnableCommand extends AbstractCommand
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
            ->setName('extensions:enable')
            ->setDescription("Enable an extension")
            ->addArgument(
                'extension',
                InputArgument::REQUIRED,
                'The extension to enable.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $extensionName = $this->input->getArgument('extension');

        if (!$this->extensions->getExtension($extensionName)) {
            $this->info('Could not find extension ' . $extensionName);

            return;
        }

        $this->info('Enabling: ' . $extensionName);

        $this->extensions->enable($extensionName);

        $this->info('DONE.');
    }
}
