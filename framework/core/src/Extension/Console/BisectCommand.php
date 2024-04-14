<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Console;

use Flarum\Extension\Bisect;
use Flarum\Extension\ExtensionManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'extension:bisect',
    description: 'Find which extensions is causing an issue. This command will progressively enable and disable extensions to find the one causing the issue. This command will put the forum in maintenance mode.'
)]
class BisectCommand extends Command
{
    public function __construct(
        protected ExtensionManager $extensions,
        protected Bisect $bisect,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->output->writeln('<info>Starting bisect...</info>');

        $start = true;

        $result = $this->bisect->checkIssueUsing(function (array $step) use (&$start) {
            if (! $start) {
                $this->output->writeln("<info>Continuing bisect... {$step['stepsLeft']} steps left</info>");
                $this->output->writeln('<info>Issue is in one of: ('.implode(', ', $step['relevantEnabled']).') or ('.implode(', ', $step['relevantDisabled']).')</info>');
            } else {
                $start = false;
            }

            return $this->output->confirm('Does the issue still occur?');
        })->run();

        if (! $result) {
            $this->output->writeln('<error>Could not find the extension causing the issue.</error>');

            return Command::FAILURE;
        }

        $this->foundIssue($result['extension']);

        return Command::SUCCESS;
    }

    protected function foundIssue(string $id): void
    {
        $extension = $this->extensions->getExtension($id);

        $title = $extension->getTitle();

        $this->output->writeln("<info>Extension causing the issue: $title</info>");
    }
}
