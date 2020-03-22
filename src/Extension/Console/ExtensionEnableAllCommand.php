<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Console\AskQuestionTrait;
use Flarum\Extension\ExtensionManager;
use Symfony\Component\Console\Input\InputOption;

class ExtensionEnableAllCommand extends AbstractCommand
{
    use AskQuestionTrait;

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
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Assume "yes" as answer to all prompts and run non-interactively.',
            )
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
        $enabled = $this->extensions->getEnabled();
        foreach ($this->extensions->getExtensions() as $extension) {
            $extensionName = $extension->getId();

            if ($this->extensions->isEnabled($extensionName)) {
                $this->info('Extension: ' . $extensionName . ' is already enabled, ignoring');
            } elseif ($this->input->getOption('only-bundled') && substr($extensionName, 0, 6) !==  'flarum') {
                $this->info('Extension: ' . $extensionName . ' is not bundled, ignoring');
            } else {
                if ($this->input->getOption('yes') || $this->confirm("Enable $extensionName?")) {
                    $this->info('Enabling: ' . $extensionName);

                    $this->extensions->enable($extensionName);
                } else {
                    $this->info('Skipping...');
                }
            }
        }
    }
}
