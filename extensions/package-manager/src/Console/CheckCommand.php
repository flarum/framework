<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Console;

use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Work\UpdateCheck;
use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Paths;
use Symfony\Component\Console\Input\InputOption;

/**
 * `php flarum extension-manager:check` — is anything newer available?
 *
 * 🚨 One cheap HTTP call per installed package, and no Composer. That is what
 * makes it safe to run nightly on somebody else's shared hosting: a resolve
 * would be 16 seconds and 165 MB, which is not something to do on a schedule on
 * a host that may only have 256 MB to begin with.
 *
 * The answer is a hint, and the admin screen says so. Whether an update can
 * actually be installed is a question for the plan, when somebody asks for it.
 */
class CheckCommand extends AbstractCommand
{
    public function __construct(private Paths $paths)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('extension-manager:check')
            ->setDescription('Check whether newer versions of installed packages exist.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Check even if the last result is still fresh.');
    }

    protected function fire(): int
    {
        $check = new UpdateCheck(StorageDir::of($this->paths->storage).'/updates.json');

        if (! $this->input->getOption('force') && ! $check->isStale()) {
            $this->info('The last check is still fresh. Use --force to check anyway.');

            return 0;
        }

        /*
         * 🚨 Extensions, Flarum, and what composer.json requires itself, asked
         * of Packagist first and then the site's own private repositories — see
         * UpdateCheck::interesting() and refreshWith(). A premium extension is
         * not on Packagist, so it used to come back "uncheckable" and its owner
         * was never told a new version existed. Packagist stays FIRST so a
         * private repository can never shadow a public package.
         */
        $result = $check->refreshSite($this->paths->base);

        if ($result === null) {
            $this->error('Could not read composer.lock, so there is nothing to check.');

            return 1;
        }

        $count = count($result['updates']);

        $this->info($count === 0
            ? 'Everything that can be checked is up to date.'
            : "$count package(s) have a newer version available.");

        foreach ($result['updates'] as $name => $move) {
            $this->info("  $name  {$move['from']} → {$move['to']}");
        }

        if ($result['uncheckable'] !== []) {
            // Said out loud rather than omitted: silence here would read as
            // "these are fine", which is not something this can know.
            $this->info(count($result['uncheckable']).' package(s) could not be checked anywhere: '
                .implode(', ', $result['uncheckable']));
        }

        /*
         * 🚨 Reported separately, because it is not a failure. A branch install
         * has no version to be newer than; whether the branch has moved is a
         * question for the update itself.
         */
        if (($result['tracking'] ?? []) !== []) {
            $this->info(count($result['tracking']).' package(s) track a branch, so there is no version to compare: '
                .implode(', ', $result['tracking']));
        }

        return 0;
    }
}
