<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Runs one of Flarum's console commands — `php flarum migrate` and friends.
 *
 * In its own process where the host allows; otherwise through Flarum's own
 * console command registry, in this process. The in-process path is only safe
 * at the start of a request that loaded the new code, which is the caller's to
 * guarantee (ComposerSteps asks FreshCode first).
 *
 * 🚨 One place for both, because there are two callers — the update's finish
 * and Restore — and the copy of "how to run a flarum command" that drifted
 * would be the one somebody only meets on a host without proc_open.
 */
final class FlarumCommand
{
    public function __construct(private string $installPath, private ComposerRunner $composer)
    {
    }

    /**
     * @param array<string,bool|string> $options e.g. ['--flush-only' => true]
     * @return array{code:int, output:string}
     */
    public function run(string $command, array $options = []): array
    {
        if ($this->composer->processes()) {
            $argv = [(string) $this->composer->php(), $this->installPath.'/flarum', $command];

            foreach ($options as $name => $value) {
                $argv[] = $value === true ? $name : $name.'='.$value;
            }

            return Process::run($argv, $this->installPath);
        }

        return $this->inProcess($command, $options);
    }

    /**
     * @param array<string,bool|string> $options
     * @return array{code:int, output:string}
     */
    private function inProcess(string $command, array $options): array
    {
        $container = \Illuminate\Container\Container::getInstance();

        if (! $container->bound('flarum.console.commands')) {
            throw new RuntimeException(Lang::trans('flarum_command.no_console', ['command' => $command]));
        }

        $found = null;

        foreach ((array) $container->make('flarum.console.commands') as $class) {
            $candidate = is_object($class) ? $class : $container->make($class);

            if ($candidate instanceof \Illuminate\Console\Command) {
                $candidate->setLaravel($container);
            }

            if ($candidate instanceof \Symfony\Component\Console\Command\Command && $candidate->getName() === $command) {
                $found = $candidate;
                break;
            }
        }

        if ($found === null) {
            throw new RuntimeException(Lang::trans('flarum_command.unknown', ['command' => $command]));
        }

        $output = new \Symfony\Component\Console\Output\BufferedOutput(
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL,
            false
        );

        return InProcess::isolate(['COLUMNS' => '120', 'LINES' => '50'], function () use ($found, $command, $options, $output): array {
            $console = new \Symfony\Component\Console\Application('Flarum');
            $console->setAutoExit(false);
            $console->setCatchExceptions(false);
            $console->add($found);

            try {
                $code = $console->run(
                    new \Symfony\Component\Console\Input\ArrayInput(['command' => $command, '--no-interaction' => true] + $options),
                    $output
                );
            } catch (\Throwable $e) {
                $code = 1;
                $output->writeln($e->getMessage());
            }

            return ['code' => $code, 'output' => trim($output->fetch())];
        });
    }
}
