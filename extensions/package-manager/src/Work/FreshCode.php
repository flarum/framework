<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Host\Opcache;
use Flarum\ExtensionManager\Run\NeedsWebRequest;
use Flarum\ExtensionManager\Run\NotYet;
use Flarum\Locale\TranslatorInterface;

/**
 * Is the code loaded in THIS process the code now on disk?
 *
 * Only asked when a step is about to run Composer or Flarum inside this
 * process, after the swap. With a subprocess the question never comes up: a
 * fresh process loads whatever is on disk. In-process, two things can be stale:
 *
 *   - Classes this process already loaded. A queue worker or a terminal
 *     running `extension-manager:update` booted Flarum before the swap and keeps
 *     those class definitions until it exits — PHP cannot unload a class.
 *     Running migrations there would run the OLD extension's code against the
 *     NEW one's files. So a long-lived process never does these steps; it
 *     waits for the admin page, whose every poll is a request of its own.
 *   - Compiled copies in opcache. A web request that starts after the swap
 *     still gets the old compiled file until opcache re-reads it. So the cache
 *     is cleared, and the step runs on the NEXT request — the clearing request
 *     itself already has its classes.
 *
 * Each wait is one poll of the admin page, about a second and a half.
 */
class FreshCode
{
    /** Longer than this and waiting for revalidation is not worth it. */
    protected const WAIT_CAP = 180;

    public function __construct(
        protected TranslatorInterface $translator,
        protected string $workDir,
        protected string $installPath,
        protected Opcache $opcache,
        protected ?string $sapi = null,
        protected ?float $requestStart = null,
    ) {
    }

    /**
     * Return when this process may run post-swap code; throw NotYet when not.
     */
    public function ensure(): void
    {
        if (($this->sapi ?? PHP_SAPI) === 'cli') {
            throw new NeedsWebRequest(
                $this->translator->trans('flarum-extension-manager.api.fresh.needs_web')
            );
        }

        $changed = $this->lastChange();
        $start = $this->requestStart ?? (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

        // A request that began before the last write has the old code by definition.
        if ($start < $changed + 1) {
            throw new NotYet($this->translator->trans('flarum-extension-manager.api.fresh.waiting_request'));
        }

        $opcache = $this->opcache;
        $situation = $opcache->situation();

        if (! $situation['enabled']) {
            return;
        }

        $marker = $this->workDir.'/opcache.cleared';
        $cleared = is_file($marker) ? (float) @file_get_contents($marker) : 0.0;

        /*
         * Cleared AFTER the last change, by an EARLIER request. A second
         * of margin on the change because filemtime has whole seconds and the
         * marker does not.
         */
        if ($cleared > $changed + 1 && $cleared < $start) {
            return;
        }

        if ($situation['canReset'] && $opcache->clear()['done']) {
            @file_put_contents($marker, sprintf('%.6F', microtime(true)));

            throw new NotYet($this->translator->trans('flarum-extension-manager.api.fresh.cleared'));
        }

        if (! $situation['validates']) {
            return;   // nothing to wait for; Opcache::clear()'s own message says restart PHP-FPM
        }

        $left = (int) ceil($changed + max(1, $situation['freq']) + 2 - $start);

        if ($left > 0 && $left <= self::WAIT_CAP) {
            throw new NotYet($this->translator->trans('flarum-extension-manager.api.composer.waiting_reread', ['seconds' => $left]));
        }
    }

    /** The newest write this run made that changes which code runs. */
    protected function lastChange(): float
    {
        clearstatcache();

        $times = array_map(fn (string $path) => (int) @filemtime($path), [
            $this->workDir.'/journal.jsonl',
            $this->installPath.'/vendor/composer/autoload_static.php',
            $this->installPath.'/vendor/composer/installed.php',
        ]);

        return (float) max($times);
    }
}
