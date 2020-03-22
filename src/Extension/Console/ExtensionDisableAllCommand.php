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

class ExtensionDisableAllCommand extends AbstractCommand
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
            ->setName('extensions:disableAll')
            ->setDescription("Disable all extensions.")
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Assume "yes" as answer to all prompts and run non-interactively.',
            )
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
            $extensionName = $extension->getId();

            if ($this->input->getOption('keep-bundled') && substr($extensionName, 0, 6) ===  'flarum') {
                $this->info('Extension: ' . $extensionName . ' is bundled, ignoring');
            } else {
                if ($this->input->getOption('yes') || $this->confirm("Disable $extensionName?")) {
                    $this->info('Disabling: ' . $extensionName);

                    $this->extensions->disable($extensionName);
                } else {
                    $this->info('Skipping...');
                }
            }
        }
    }
}
