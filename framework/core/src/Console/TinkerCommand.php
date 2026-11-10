<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class TinkerCommand extends AbstractCommand
{
    public function __construct(
        protected Container $container,
        protected Paths $paths
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('tinker')
            ->setDescription('Interact with your Flarum installation through an interactive PHP shell (REPL)')
            ->addOption('execute', 'e', InputOption::VALUE_REQUIRED, 'Execute the given code, print the result, and exit (non-interactive)');
    }

    protected function fire(): int
    {
        $code = $this->input->getOption('execute');

        // Keep PsySH's runtime files (and command history) inside Flarum's
        // storage directory. The default location relies on a writable $HOME /
        // system temp dir, which often isn't available for the web/CLI user on
        // a server — and getRuntimeDir() throws outright if it can't be created.
        $runtimeDir = $this->paths->storage.'/tmp/psysh';

        // Mirror the console's own colour decision (TTY detection plus the
        // --ansi / --no-ansi flags) onto PsySH. Its default `auto` mode does
        // its own detection, which loses colour whenever output isn't a direct
        // TTY (e.g. through Docker) and ignores --ansi; deferring to Symfony's
        // decision gives colour interactively and clean text when piped.
        $colorMode = $this->output->isDecorated()
            ? Configuration::COLOR_MODE_FORCED
            : Configuration::COLOR_MODE_DISABLED;

        $config = new Configuration([
            'updateCheck' => Checker::NEVER,
            'runtimeDir' => $runtimeDir,
            'historyFile' => $runtimeDir.'/history',
            'colorMode' => $colorMode,
            'prompt' => 'flarum> ',
            'startupMessage' => implode("\n", [
                '<info>Flarum '.Application::VERSION.' tinker — interactive shell.</info>',
                '<info>Available: <comment>$container</comment>, <comment>$settings</comment>, <comment>$db</comment>, <comment>$events</comment>, <comment>$extensions</comment>, <comment>resolve()</comment>, and models by short name (e.g. <comment>User::find(1)</comment>).</info>',
                '<info>Type <comment>flarum</comment> for this list again, or <comment>help</comment> for shell commands. Docs: <comment>https://docs.flarum.org/2.x/console#tinker</comment></info>',
            ]),
        ]);

        // When code is executed non-interactively, don't try to source ~/.psysh
        // config or write history — just run and exit cleanly.
        if ($code !== null) {
            $config->setInteractiveMode(Configuration::INTERACTIVE_MODE_DISABLED);
        }

        $shell = new Shell($config);
        $shell->setScopeVariables([
            'container' => $this->container,
            'settings' => $this->container->make(SettingsRepositoryInterface::class),
            'db' => $this->container->make(ConnectionInterface::class),
            'events' => $this->container->make(Dispatcher::class),
            'extensions' => $this->container->make(ExtensionManager::class),
        ]);

        // Add an in-shell `flarum` command that reprints the available
        // variables and helpers; it also shows up in PsySH's `help` list.
        $shell->addCommand(new TinkerHelpCommand);

        // Allow referencing Eloquent models by their short name, e.g.
        // `User::find(1)` instead of `Flarum\User\User::find(1)`.
        $aliasLoader = ModelAliasAutoloader::register(fn (string $message) => $this->output->writeln($message));

        if ($aliasLoader === null && $code === null) {
            $this->output->writeln('<comment>Note: could not locate the Composer autoloader, so referencing models by their short name is unavailable. Use fully-qualified class names instead.</comment>');
        }

        try {
            // With --execute, run the snippet, print its return value, and exit
            // instead of dropping into the interactive prompt. This is the
            // reliable way to run tinker non-interactively (e.g. in scripts) —
            // piping code into stdin is unreliable because of how the REPL
            // buffers input.
            if ($code !== null) {
                // execute() doesn't set up the shell's output the way run()
                // does, so give it PsySH's configured output first.
                $shell->setOutput($config->getOutput());

                // Pass throwExceptions so a failing snippet surfaces a non-zero
                // exit code (important for scripting/CI) rather than being
                // swallowed.
                try {
                    $result = $shell->execute($code, true);
                } catch (\Throwable $e) {
                    $this->error($e->getMessage());

                    return Command::FAILURE;
                }

                // Write the result through the command's own output (rather than
                // PsySH's raw STDOUT stream) so it's consistent with every other
                // Flarum command and can be captured or redirected.
                $this->output->writeln('=> '.$config->getPresenter()->present($result));

                return Command::SUCCESS;
            }

            return $shell->run();
        } finally {
            // Don't leave the fallback autoloader registered after the shell
            // exits — matters when the command runs more than once in a single
            // process (e.g. integration tests).
            $aliasLoader?->unregister();
        }
    }
}
