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

class ExtensionEnableCommand extends AbstractCommand
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
            ->setName('extensions:enable')
            ->setDescription('Enable extensions')
            ->addArgument(
                'extensions',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'A space-separated list of Flarum Extension IDs to enable'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Enable all extensions. This overrides all other options.',
            )
            ->addOption(
                'include-bundled',
                'b',
                InputOption::VALUE_NONE,
                'Enable all bundled extensions.',
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
        $extensions = $this->getExtensionsToEnable();

        if (empty($extensions)) {
            $this->info('No extensions to process.');
        }

        foreach ($extensions as $extension) {
            if ($this->input->getOption('yes') || $this->confirm("Enable $extension?")) {
                $this->info("Enabling: $extension");

                $this->extensions->enable($extension);
            } else {
                $this->info('Skipping...');
            }
        }

        $this->info('Done!');
    }

    protected function allExtensions()
    {
        return $this->extensions->getExtensions()->map(function ($extension) {
            return $extension->getId();
        })->toArray();
    }

    protected function getExtensionsToEnable()
    {
        $extensionIds = [];

        foreach ($this->input->getArgument('extensions') as $extension) {
            if (! $this->extensions->getExtension($extension)) {
                $this->error("Extension: $extension is not installed, ignoring.");
            } elseif (! in_array($extension, $extensionIds)) {
                $extensionIds[] = $extension;
            }
        }

        if ($this->input->getOption('all')) {
            $extensionIds = $this->allExtensions();
        } elseif ($this->input->getOption('include-bundled')) {
            $extensionIds = array_merge($extensionIds, array_filter($this->allExtensions(), function ($extension) use ($extensionIds) {
                if (substr($extension, 0, 7) !== 'flarum-' && ! in_array($extension, $extensionIds)) {
                    $this->info("Extension: $extension is not bundled and not explicitly specified, ignoring");

                    return false;
                }

                return true;
            }));
        }

        $extensionIds = array_filter($extensionIds, function ($extension) {
            if ($this->extensions->isEnabled($extension)) {
                $this->info("Extension: $extension is already enabled, ignoring");

                return false;
            }

            return true;
        });

        return $extensionIds;
    }
}
