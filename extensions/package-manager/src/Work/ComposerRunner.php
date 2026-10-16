<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Host\PhpBinary;
use RuntimeException;

/**
 * Runs Composer: in its own process where the host allows it, inside this one
 * where it does not.
 *
 * 🚨 A subprocess by preference. The previous Extension Manager runs Composer inside the PHP
 * worker serving the request AND lets it rewrite vendor/, so a killed request
 * can take the vendor directory with it and a booted Flarum shares the
 * resolve's memory budget. Out of process, Composer gets its own limit, its own
 * lifetime, and its failures are exit codes rather than a dead worker.
 *
 * 🚨 In-process where a subprocess is impossible — proc_open disabled, or no
 * command-line PHP — and this used to refuse instead. What made Extension
 * Manager fragile was never the process Composer ran in: it was Composer
 * writing vendor/ from inside a request that could be killed. The Extension Manager never
 * lets Composer touch a package directory. Composer only RESOLVES (`--no-install`
 * writes composer.lock and nothing else), Fetcher downloads, and Applier swaps
 * directories with journalled renames. The one write Composer makes here is the
 * autoloader at the register step, and ComposerSteps guards that separately.
 * So running the resolver in the request costs memory and time, both of which
 * are asked for and reported, and nothing that a kill can break.
 *
 * Shared hosting is where this matters: on IONOS/Plesk and most cPanel plans
 * proc_open is disabled for the website, and the Extension Manager could not run there at
 * all.
 */
class ComposerRunner
{
    private PhpBinary $php;

    public function __construct(
        private string $installPath,
        private ?string $composerBin = null,
        private ?string $composerHome = null,
        ?string $phpBin = null,
    ) {
        $this->php = new PhpBinary($phpBin);
    }

    /** The same runner, judging the host through a different PhpBinary. */
    public function withPhp(PhpBinary $php): self
    {
        $copy = clone $this;
        $copy->php = $php;

        return $copy;
    }

    /** The command-line PHP every subprocess the Extension Manager starts is run with. */
    public function php(): ?string
    {
        return $this->php->path();
    }

    /**
     * Composer will run inside this PHP process rather than in its own.
     *
     * 🚨 Asked of THIS process. A queue worker's PHP often has a different
     * php.ini from the website's, so the same run can spawn from the worker and
     * run in-process from the admin page — and both are fine.
     */
    public function inProcess(): bool
    {
        return ! $this->canSpawn() && class_exists(\Composer\Console\Application::class);
    }

    /** A command-line PHP can be started: proc_open allowed, and one was found. */
    public function processes(): bool
    {
        return $this->php->canSpawn() && $this->php->path() !== null;
    }

    public function canSpawn(): bool
    {
        return $this->processes() && $this->binary() !== null;
    }

    /**
     * @param list<string> $args
     * @return array{code:int, output:string}
     */
    public function run(array $args, int $timeout = 600): array
    {
        if (! $this->canSpawn()) {
            if ($this->inProcess()) {
                return $this->runInProcess($args);
            }

            throw new RuntimeException($this->whyNot());
        }

        $cmd = array_merge(
            [$this->php->path(), $this->binary()],
            $args,
            ['--no-interaction', '--working-dir='.$this->installPath]
        );

        return Process::run($cmd, $this->installPath, [
            'PATH' => getenv('PATH') ?: '/usr/bin:/bin',
            /*
             * 🚨 -1, set explicitly. Composer raises its own limit to 1.5G on
             * startup unless this is set, which would make any measurement of
             * what a host can actually do meaningless — and the whole capability
             * panel depends on those measurements being real.
             */
            'COMPOSER_MEMORY_LIMIT' => '-1',
            'COMPOSER_HOME' => $this->composerHome(),
            'HOME' => getenv('HOME') ?: $this->installPath,
        ], $timeout);
    }

    /**
     * The same command, through Composer's own console application, in this
     * process.
     *
     * 🚨 An argv ARRAY, exactly as the subprocess gets, and never a string to
     * be parsed: the only user-supplied values are package names that
     * StartController has already matched against Composer's own pattern.
     *
     * @param list<string> $args
     * @return array{code:int, output:string}
     */
    public function runInProcess(array $args): array
    {
        /*
         * 🚨 No plugins and no scripts, in-process always. Either would be
         * somebody else's code running inside this request — and most of
         * them start a program anyway, which is the thing this host forbids.
         */
        $argv = array_merge(
            ['composer'],
            $args,
            array_values(array_diff(['--no-plugins', '--no-scripts'], $args)),
            ['--no-interaction', '--working-dir='.$this->installPath]
        );

        // Composer's own console output, never the page's.
        $output = new \Symfony\Component\Console\Output\BufferedOutput(
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL,
            false
        );

        return InProcess::isolate($this->inProcessEnv(), function (array $limits) use ($argv, $output): array {
            $application = new \Composer\Console\Application();
            $application->setAutoExit(false);
            $application->setCatchExceptions(false);

            try {
                $code = $application->run(new \Symfony\Component\Console\Input\ArgvInput($argv), $output);
            } catch (\Throwable $e) {
                $code = 1;
                $output->writeln(self::explainThrowable($e));
            }

            $said = trim($output->fetch());

            if ($code !== 0 && (! $limits['memory'] || ! $limits['time'])) {
                $said .= "\n".self::limitNote($limits);
            }

            return ['code' => $code, 'output' => $said];
        });
    }

    /**
     * 🚨 Every variable that changes what Composer does, set the way the
     * subprocess sets them — plus the one that replaces a call to git.
     *
     * COMPOSER_ROOT_VERSION: without it Composer asks git what version the
     * forum itself is, which is a process. With it, a Packagist-only resolve
     * runs no program at all. Left alone if the host already sets it.
     *
     * @return array<string,string>
     */
    private function inProcessEnv(): array
    {
        $env = [
            'COMPOSER_HOME' => $this->composerHome(),
            'COMPOSER_MEMORY_LIMIT' => '-1',
            'COMPOSER_NO_INTERACTION' => '1',
            // The security audit is more network calls on a clock that is already running.
            'COMPOSER_NO_AUDIT' => '1',
            // Symfony measures the terminal with `stty` when these are absent.
            'COLUMNS' => '120',
            'LINES' => '50',
        ];

        if (getenv('COMPOSER_ROOT_VERSION') === false && ! isset($_SERVER['COMPOSER_ROOT_VERSION'])) {
            $env['COMPOSER_ROOT_VERSION'] = '1.0.0';
        }

        if ((getenv('HOME') ?: ($_SERVER['HOME'] ?? '')) === '') {
            $env['HOME'] = $this->installPath;
        }

        return $env;
    }

    /** Where Composer keeps its cache and global auth: under storage/, so it survives between requests. */
    public function composerHome(): string
    {
        return $this->composerHome ?? ($this->installPath.'/storage/.composer');
    }

    /**
     * 🚨 Composer reaching for git on a host that cannot start programs throws
     * Symfony's "relies on proc_open" LogicException, from deep inside a VCS or
     * path repository. Turned into the sentence that says what to do.
     */
    public static function explainThrowable(\Throwable $e): string
    {
        for ($t = $e; $t !== null; $t = $t->getPrevious()) {
            if (str_contains($t->getMessage(), 'proc_open')) {
                return Lang::trans('composer.needs_git');
            }
        }

        return $e->getMessage();
    }

    /**
     * @param array{memory:bool, time:bool} $limits
     */
    private static function limitNote(array $limits): string
    {
        $parts = [];

        if (! $limits['memory']) {
            $parts[] = Lang::trans('composer.limit_memory', ['limit' => (string) ini_get('memory_limit')]);
        }

        if (! $limits['time']) {
            $parts[] = Lang::trans('composer.limit_time', ['seconds' => (string) ini_get('max_execution_time')]);
        }

        return Lang::trans('composer.limit_note', ['limits' => implode(Lang::trans('composer.limit_joiner'), $parts)]);
    }

    /**
     * 🚨 The BUNDLED Composer first, and a host's own only as a last resort.
     *
     * The Extension Manager requires composer/composer, so the library is in the same
     * vendor tree as everything else and is simply always there. That is not
     * belt-and-braces: a great many shared hosts have no `composer` command at
     * all, and the ones that do are running whatever version their panel
     * shipped. Depending on the host's would make the behaviour of an update
     * differ per host in ways nobody could reproduce.
     *
     * Run as a subprocess wherever one can be started: same library, own
     * process, own limit. The in-process fallback uses this same bundled
     * library through its autoloader.
     */
    private function binary(): ?string
    {
        if ($this->composerBin !== null) {
            return $this->composerBin;
        }

        $candidates = [
            $this->installPath.'/vendor/composer/composer/bin/composer',
            $this->installPath.'/vendor/bin/composer',
            $this->installPath.'/composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $this->composerBin = $candidate;
            }
        }

        return null;
    }

    /**
     * Reached only when neither way can work: no subprocess, and no Composer
     * library to run in-process either.
     */
    private function whyNot(): string
    {
        return Lang::trans('composer.missing');
    }
}
