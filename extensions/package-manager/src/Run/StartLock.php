<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

/**
 * One start at a time, for the whole installation.
 *
 * Starting a run is two steps: ask whether an unfinished run is in the way,
 * then write the new one down. Two requests arriving together (a double click,
 * two tabs, the screen and the command line) both got a "no" to the first
 * question before either had done the second, and both runs then rewrote the
 * manifests and vendor/ with a journal each. Neither journal knew about the
 * other's changes, so neither could put them back.
 *
 * Held only around the check and the creation, and never waited for. A
 * second start that finds it held is told an update is already starting,
 * which is true, rather than queued behind the first.
 */
class StartLock
{
    /** @var resource|null */
    protected $handle = null;

    public function __construct(
        protected string $lockDir,
    ) {
    }

    /**
     * True when this process now holds the lock, false when somebody else
     * does.
     *
     * @throws StartLockUnwritable when the lock file cannot be made at all.
     *   Starting without the lock is exactly the race this class exists to
     *   stop, so that refuses the start too, but says why: unlike a lock that
     *   is held, it will not go away by itself.
     */
    public function acquire(): bool
    {
        if ($this->handle !== null) {
            return true;
        }

        if (! is_dir($this->lockDir) && ! @mkdir($this->lockDir, 0775, true) && ! is_dir($this->lockDir)) {
            throw new StartLockUnwritable($this->lockDir);
        }

        // Not a run id, so it can never share a file with a run's own lock.
        $handle = @fopen($this->lockDir.'/start.lock', 'c');

        if ($handle === false) {
            throw new StartLockUnwritable($this->lockDir);
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function release(): void
    {
        if ($this->handle === null) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }
}
