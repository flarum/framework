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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExtensionDisableCommand extends AbstractCommand
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
            ->setName('extensions:disable')
            ->setDescription('Disable extensions')
            ->addArgument(
                'extensions',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'A space-separated list of Flarum Extension IDs to disable'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Disable all extensions. This overrides all other options.',
            )
            ->addOption(
                'all-except-bundled',
                'b',
                InputOption::VALUE_NONE,
                'Disable all except bundled extensions.',
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Assume "yes" as answer to all prompts and run non-interactively.',
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $extensions = $this->getExtensionsToDisable();

        foreach ($extensions as $extension) {
            if ($this->input->getOption('yes') || $this->confirm("Disable $extension?")) {
                $this->info("Disabling: $extension");

                $this->extensions->disable($extension);
            } else {
                $this->info('Skipping...');
            }
        }

        $this->info('Done!');
    }

    protected function allEnabledExtensions()
    {
        return array_map(function ($extension) {
            return $extension->getId();
        }, $this->extensions->getEnabledExtensions());
    }

    protected function getExtensionsToDisable()
    {
        $extensionIds = [];

        if (empty($extensions)) {
            $this->info('No extensions to process.');
        }

        foreach ($this->input->getArgument('extensions') as $extension) {
            if (!$this->extensions->getExtension($extension)) {
                $this->error("Extension: $extension is not installed, ignoring.");
            } elseif (!in_array($extension, $extensionIds)) {
                $extensionIds[] = $extension;
            }
        }

        if ($this->input->getOption('all')) {
            $extensionIds = $this->allEnabledExtensions();
        } elseif ($this->input->getOption('all-except-bundled')) {
            $extensionIds = array_merge($extensionIds, array_filter($this->allEnabledExtensions(), function ($extension) use ($extensionIds) {
                if (substr($extension, 0, 6) === 'flarum'  && ! in_array($extension, $extensionIds)) {
                    $this->info("Extension: $extension is bundled and not explicitly specified, ignoring");
                    return false;
                }
                return true;
            }));
        }

        $extensionIds = array_filter($extensionIds, function ($extension) {
            if (! $this->extensions->isEnabled($extension)) {
                $this->info("Extension: $extension is already disabled, ignoring");
                return false;
            }
            return true;
        });

        return $extensionIds;
    }
}
