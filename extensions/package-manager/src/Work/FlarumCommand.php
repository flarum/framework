<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\Locale\TranslatorInterface;
use RuntimeException;

/**
 * Runs one of Flarum's console commands — `php flarum migrate` and friends.
 *
 * In its own process where the host allows; otherwise through Flarum's own
 * console command registry, in this process. The in-process path is only safe
 * at the start of a request that loaded the new code, which is the caller's to
 * guarantee (ComposerSteps asks FreshCode first).
 *
 * One place for both, because there are two callers — the update's finish
 * and Restore — and the copy of "how to run a flarum command" that drifted
 * would be the one somebody only meets on a host without proc_open.
 */
class FlarumCommand
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected string $installPath,
        protected ComposerRunner $composer,
    ) {
    }

    /**
     * @param array<string,bool|string> $options e.g. ['--flush-only' => true]
     * @return array{code:int, output:string}
     */
    public function run(string $command, array $options = []): array
    {
        if ($this->composer->processes()) {
            /*
             * No memory limit, as Composer gets (COMPOSER_MEMORY_LIMIT) and as
             * the in-process path already takes. Flarum 2's cache:clear compiles
             * every frontend in one process, and less.php's cache stores each
             * parsed node with its own copy of the file path (a 12 KB stylesheet
             * caches as ~4 MB), so the peak grows with the LESS a forum's
             * extensions ship: 597 MB on dev, past a customer's 128 MB CLI limit
             * with 26 extensions (2026-10-18), failing every update at its end.
             */
            $argv = [(string) $this->composer->php(), '-d', 'memory_limit=-1', $this->installPath.'/flarum', $command];

            foreach ($options as $name => $value) {
                $argv[] = $value === true ? $name : $name.'='.$value;
            }

            return Process::run($this->translator, $argv, $this->installPath);
        }

        return $this->inProcess($command, $options);
    }

    /**
     * What to show of a failed command's output: its head AND its tail.
     *
     * PHP, Xdebug and Symfony all print the error FIRST and the stack after
     * it. Keeping only the last twelve lines kept the frames and dropped the one
     * line that named the failure: a customer's `cache:clear` (2026-10-18) came
     * back as twelve Xdebug frames, each kilobytes of dumped arguments, and no
     * error at all. Lines are capped too, so one frame cannot bury the rest.
     */
    public static function excerpt(string $output, int $head = 4, int $tail = 8): string
    {
        $lines = array_map(
            fn (string $line) => mb_strimwidth(rtrim($line), 0, 300, '…'),
            array_values(array_filter(explode("\n", $output), fn (string $line) => trim($line) !== ''))
        );

        if (count($lines) > $head + $tail) {
            $lines = [...array_slice($lines, 0, $head), '…', ...array_slice($lines, -$tail)];
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string,bool|string> $options
     * @return array{code:int, output:string}
     */
    protected function inProcess(string $command, array $options): array
    {
        $container = \Illuminate\Container\Container::getInstance();

        if (! $container->bound('flarum.console.commands')) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.flarum_command.no_console', ['command' => $command]));
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
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.flarum_command.unknown', ['command' => $command]));
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
