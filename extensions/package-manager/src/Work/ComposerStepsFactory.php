<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Apply\Applier;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\ExtensionManager\Run\StepsFactory;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;

/**
 * Assembles a run's Composer, downloader and applier around that run's own
 * scratch space.
 *
 * Everything here is derived from the id, and nothing is remembered between
 * calls: hand it a run id and it builds the whole apparatus for that run, which
 * is what lets a driver holding nothing but an id — a cron tick, a worker, an
 * admin page that has just been reopened — pick up work it did not start.
 */
class ComposerStepsFactory implements StepsFactory
{
    /**
     * 🚨 `$config` is not optional, and must not be made optional again.
     *
     * It was `?Config $config = null`, and the one place that constructs this
     * did not pass it. The result was silent: no error, no warning, just an
     * empty site address, and with it a health check and an automatic rollback
     * that never ran anywhere. A required parameter turns that into a failure
     * at construction, where somebody would see it.
     */
    public function __construct(private Paths $paths, private Config $config)
    {
    }

    /**
     * 🚨 The site's OWN configured address, and an empty string when there
     * isn't one.
     *
     * Not a guess from the request, because the process that runs a step is
     * usually a queue worker with no request to guess from. And empty rather
     * than a fallback like localhost: a health check pointed at the wrong place
     * is worse than none, since it would either pass while the real site is
     * down or fail while it is fine — and the second one now undoes updates.
     */
    private function siteUrl(): string
    {
        try {
            $url = (string) $this->config->url();
        } catch (\Throwable $e) {
            return '';
        }

        return str_starts_with($url, 'http') ? rtrim($url, '/').'/' : '';
    }

    public function for(string $runId): Steps
    {
        $workDir = new WorkDir($this->paths->storage, $runId);
        $journal = new Journal($workDir->journalPath());

        return new ComposerSteps(
            $this->paths->base,
            $workDir->root(),
            new ComposerRunner(
                $this->paths->base,
                null,
                $this->paths->storage.'/.composer',
                (new \Flarum\ExtensionManager\Host\PhpOverride($this->paths->storage))->get()
            ),
            new Fetcher($workDir->staging(), $this->paths->base.'/auth.json'),
            new Applier($this->paths->vendor, $workDir->staging(), $workDir->trash(), $journal),
            $journal,
            $workDir->requested(),
            $workDir->mode(),
            $this->paths->vendor,
            $this->paths->storage,
            $this->siteUrl(),
            $workDir->repin()
        );
    }
}
