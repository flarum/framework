<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Psy\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An in-shell `flarum` command that reprints the Flarum-specific context
 * (scope variables, short-name model aliasing, docs link). This is the same
 * information shown in the startup banner, made available on demand for once
 * the banner has scrolled out of view. It is listed automatically by PsySH's
 * built-in `help` command.
 */
class TinkerHelpCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('flarum')
            ->setDefinition([])
            ->setDescription('Show Flarum tinker help (available variables and helpers).')
            ->setHelp('Show the Flarum-specific variables and helpers available in this shell.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '<info>Flarum tinker</info>',
            '',
            '  <comment>$container</comment>    The application (service container)',
            '  <comment>$settings</comment>     The settings repository',
            '  <comment>$db</comment>           The database connection',
            '  <comment>$events</comment>       The event dispatcher',
            '  <comment>$extensions</comment>   The extension manager',
            '  <comment>resolve(...)</comment>  Resolve any other binding from the container',
            '',
            '  Eloquent models can be referenced by their short name, e.g. <comment>User::find(1)</comment>.',
            '',
            '  Docs: <comment>https://docs.flarum.org/2.x/console#tinker</comment>',
        ]);

        return self::SUCCESS;
    }
}
