<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\Locale\TranslatorInterface;

/**
 * One holder at a time for a run: the step driver doing an item, a rollback,
 * and the second half of an undo that reverses migrations.
 *
 * Each of these changes vendor/, the manifests or the database for the same
 * run, and two of them at once is how a rollback ends up racing a queue
 * worker that is still applying. Non-blocking: a second caller is told the
 * run is busy and asks again later, rather than queueing behind a step that
 * can legitimately take ten minutes.
 */
class RunLock
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected ?string $dir,
    ) {
    }

    /**
     * The held lock, or null when another caller has it.
     *
     * A lock that cannot be made at all is an error, not "busy" and not "no
     * lock": a lock file left owned by another user (the command line run as
     * root) would otherwise say "busy" forever, and a directory that cannot be
     * created would quietly turn locking off.
     *
     * @return resource|null
     */
    public function acquire(string $id)
    {
        if ($this->dir === null) {
            return fopen('php://memory', 'r+') ?: null;   // no lock configured (tests)
        }

        if (! is_dir($this->dir) && ! @mkdir($this->dir, 0775, true) && ! is_dir($this->dir)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.run.lock_dir_failed', ['path' => $this->dir]), 'lock_failed', 500);
        }

        $path = $this->dir.'/'.preg_replace('/[^A-Za-z0-9_-]/', '', $id).'.lock';
        $handle = @fopen($path, 'c');

        if ($handle === false) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.run.lock_failed', ['path' => $path]), 'lock_failed', 500);
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    /** @param resource $handle */
    public function release($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
