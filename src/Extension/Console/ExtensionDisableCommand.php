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
            ->setDescription('Disable an extension')
            ->addArgument(
                'extensions',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'The extensions to disable. You must provide this in extension-id format, that is: VENDOR_NAME-EXTENSION_NAME, based on the composer package, where "flarum-" or "flarum-ext-" is stripped from the extension name. For example, "flarum\tags" has an id of "flarum-tags", and "some-programmer/flarum-extension" has an id of "some-programmer-extension"'
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
        if ($this->input->getOption('all')) {
            // All Extensions
            $extensionIds = $this->allEnabledExtensions();
        } elseif ($this->input->getOption('all-except-bundled')) {
            // All exc
            $extensionIds = array_filter($this->allEnabledExtensions(), function ($extension) {
                return substr($extension, 0, 6) !== 'flarum' ?: $this->info("Extension: $extension is bundled, ignoring") && false;
            });
        }

        // Process user-specified extensions
        foreach ($this->input->getArgument('extensions') as $extension) {
            if (!$this->extensions->getExtension($extension)) {
                $this->error("Extension: $extension is not installed.");
            } elseif (!in_array($extension, $extensionIds)) {
                $extensionIds[] = $extension;
            }
        }

        $extensionIds = array_filter($extensionIds, function ($extension) {
            return $this->extensions->isEnabled($extension) ?:$this->info("Extension: $extension is already disabled, ignoring") && false;
        });

        return $extensionIds;
    }
}
