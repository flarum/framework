<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Apply;

use Flarum\ExtensionManager\Plan\Change;
use Flarum\Locale\TranslatorInterface;
use RuntimeException;

/**
 * Puts a plan into the vendor tree, one package at a time, writing down each
 * step before it takes it.
 *
 * Two renames, never a delete.
 *
 *     vendor/<pkg>   → trash/<pkg>@<version>     (the old one is kept)
 *     staging/<pkg>  → vendor/<pkg>              (the new one arrives)
 *
 * Both are `rename()` (Tree::move), so each is atomic and effectively instantaneous. The
 * package is absent between them, and only between them: microseconds, for one
 * package, where deleting a tree and moving another in takes far longer.
 * Nothing is deleted at all during an apply: the old tree waits in the
 * trash until somebody prunes it, which is what makes rollback possible long
 * after the fact.
 *
 * POSIX cannot swap two directories atomically, so two renames is the floor. It
 * is not worth reaching for something cleverer.
 *
 * The applier does not resolve, download, or decide anything. It is handed a
 * list and a staging directory, and its only job is to be interruptible.
 */
class Applier
{
    /**
     * @param ?callable $observer Called after each filesystem step with a label
     *        and the change. Serves two purposes, which is why it earns its
     *        place in production code: the step driver reports progress through
     *        it, and the crash tests kill the process from it, which is the only
     *        way to prove the journal survives an interruption at each point
     *        rather than at a convenient one.
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected string $vendorDir,
        protected string $stagingDir,
        protected string $trashDir,
        protected Journal $journal,
        protected $observer = null,
    ) {
    }

    protected function observe(string $label, Change $change): void
    {
        if ($this->observer !== null) {
            ($this->observer)($label, $change);
        }
    }

    /**
     * @param list<Change> $changes
     * @return list<string> what was applied, in order, for the caller to report
     */
    public function apply(array $changes): array
    {
        if ($this->journal->exists() && ! $this->journal->isComplete()) {
            /*
             * Refuse rather than continue. A journal with an unfinished entry
             * means a previous apply died, and the tree is in a state only that
             * journal can explain. Starting a second apply on top of it would
             * interleave two records and make both unreadable — which is how a
             * recoverable situation becomes a manual one.
             */
            throw new RuntimeException(
                $this->translator->trans('flarum-extension-manager.api.apply.previous_unfinished')
            );
        }

        $done = [];

        foreach ($changes as $change) {
            $done[] = $this->applyOne($change);
        }

        return $done;
    }

    /**
     * One package, journalled.
     *
     * No "did the last run finish" guard here, unlike apply(). When the step
     * driver is doing the sequencing, an unfinished journal is the NORMAL state
     * — it is the record of the run currently in progress. Refusing on it would
     * make the second step of every update fail.
     */
    public function applyOne(Change $change): string
    {
        $this->refusePathInstall($change);

        $this->ensureDir($this->trashDir);

        /*
         * The step driver saves its progress after this returns, and after the
         * work that follows it in the same request (pruning autoload files,
         * bringing core's database up to date, which can take minutes). A
         * process killed in that window asks for the same change again, so
         * this must be safe to repeat. Repeating it blindly was not: the new
         * version was in vendor/, the stash code took it for the old one, and
         * the only copy of the old version was deleted to make room for it.
         */
        $previous = $this->journal->lastFor($change->toArray());

        if ($previous !== null && Journal::isDone($previous)) {
            return $change->describe($this->translator);
        }

        if ($previous !== null) {
            // Resuming the change that was in flight: carry on under its own
            // record rather than starting a second one for the same move.
            $seq = (int) $previous['seq'];

            if ($this->alreadyInPlace($change)) {
                $this->journal->complete($seq);
                $this->observe('completed', $change);

                return $change->describe($this->translator);
            }
        } else {
            $seq = $this->journal->begin([
                'change' => $change->toArray(),
                'trash' => $change->trashName(),
            ]);
        }

        $this->observe('journalled', $change);

        match ($change->op) {
            Change::REPLACE => $this->replace($change),
            Change::ADD => $this->add($change),
            Change::REMOVE => $this->remove($change),
            default => throw new RuntimeException("Unknown change \"{$change->op}\" for {$change->package}"),
        };

        $this->journal->complete($seq);
        $this->observe('completed', $change);

        return $change->describe($this->translator);
    }

    /**
     * Whether an interrupted change got as far as moving the new version in.
     *
     * Staging is emptied by that very rename, so nothing staged and a live
     * copy present can only mean the install happened and only its record is
     * missing. A removal is never "in place" this way; repeating it is
     * already harmless, because there is nothing left to stash.
     */
    protected function alreadyInPlace(Change $change): bool
    {
        if ($change->op === Change::REMOVE) {
            return false;
        }

        return ! is_dir($this->path($this->stagingDir, $change->relativePath()))
            && is_dir($this->path($this->vendorDir, $change->relativePath()));
    }

    /**
     * A package installed as a symlink belongs to somebody else.
     *
     * Composer installs a path repository by linking vendor/<pkg> at a checkout
     * on disk — the way every extension developer runs their own work, and the
     * way this forum is set up. Replacing one would move that link to the trash
     * and put a downloaded copy where it stood: the developer's forum quietly
     * stops using the tree they are editing, and nothing on screen says so.
     *
     * Refused BEFORE the journal is opened, so a refusal leaves no half-record
     * to reason about later. The message names the situation rather than the
     * symptom, because "could not move" would send somebody hunting for a
     * permissions problem that does not exist.
     */
    protected function refusePathInstall(Change $change): void
    {
        $live = $this->path($this->vendorDir, $change->relativePath());

        if (is_link($live)) {
            throw new RuntimeException(
                $this->translator->trans('flarum-extension-manager.api.apply.local_path', ['package' => $change->package, 'path' => 'vendor/'.$change->relativePath()])
            );
        }
    }

    protected function replace(Change $change): void
    {
        $this->stash($change);
        $this->observe('stashed', $change);

        $this->install($change);
        $this->observe('installed', $change);
    }

    protected function add(Change $change): void
    {
        $this->install($change);
        $this->observe('installed', $change);
    }

    protected function remove(Change $change): void
    {
        $this->stash($change);
        $this->observe('stashed', $change);

        /*
         * Only on a REMOVE. A REPLACE stashes and then installs into the same
         * parent, so tidying between the two would delete a directory that is
         * about to be recreated.
         */
        $this->tidyVendorDir(dirname($this->path($this->vendorDir, $change->relativePath())));
    }

    /**
     * Remove the vendor namespace directory if that was the last package in it.
     *
     * Only when empty, and never above the vendor directory itself. rmdir
     * refuses a non-empty directory, so the check and the act cannot disagree.
     * An uninstall that leaves vendor/acme/ behind is not wrong so much as
     * unfinished, and it is the sort of litter that makes somebody wonder what
     * else was left half done.
     */
    protected function tidyVendorDir(string $dir): void
    {
        $vendor = rtrim($this->vendorDir, DIRECTORY_SEPARATOR);

        if ($dir === $vendor || ! str_starts_with($dir, $vendor.DIRECTORY_SEPARATOR)) {
            return;
        }

        @rmdir($dir);
    }

    /** Move the installed version aside. Kept, never deleted. */
    protected function stash(Change $change): void
    {
        $live = $this->path($this->vendorDir, $change->relativePath());

        if (! is_dir($live)) {
            /*
             * Already absent. Not an error: a resumed apply reaches here after
             * the stash it is repeating already happened, and a plan built
             * against a slightly stale tree can name a package somebody removed
             * by hand. Either way there is nothing to preserve.
             */
            return;
        }

        $target = $this->path($this->trashDir, $change->trashName());

        // A copy-move killed mid-delete: the stash is whole, finish it.
        if (Tree::finishMove($live, $target)) {
            return;
        }

        if (is_dir($target)) {
            /*
             * Something already holds the stash name. It may be the only copy
             * of the old version, so it is moved out of the way, never deleted:
             * a wrong guess here costs a package's last good copy.
             */
            $aside = $target.'.earlier-'.bin2hex(random_bytes(4));

            if (! Tree::move($target, $aside)) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.apply.move_aside_failed', ['from' => $target, 'to' => $aside]));
            }
        }

        $this->ensureDir(dirname($target));

        if (! Tree::move($live, $target)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.apply.move_aside_failed', ['from' => $live, 'to' => $target]));
        }
    }

    /** Move the staged version into place. */
    protected function install(Change $change): void
    {
        $staged = $this->path($this->stagingDir, $change->relativePath());

        if (! is_dir($staged)) {
            throw new RuntimeException(
                "Nothing staged for {$change->package} at $staged — fetch must run before apply"
            );
        }

        $live = $this->path($this->vendorDir, $change->relativePath());

        if (is_dir($live)) {
            /*
             * Only reachable on an ADD whose package already exists, or a resumed
             * REPLACE whose stash succeeded and whose install is being repeated.
             * Moving it aside rather than deleting keeps the no-deletes rule
             * intact even on the paths nobody expects to hit.
             */
            $orphan = $this->path($this->trashDir, $change->trashName().'.superseded');
            Tree::delete($orphan);
            Tree::move($live, $orphan);
        }

        $this->ensureDir(dirname($live));

        if (! Tree::move($staged, $live)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.apply.move_in_failed', ['from' => $staged, 'to' => $live]));
        }
    }

    /**
     * Join a trusted base with a relative path that has already been
     * validated by Change::relativePath(). Kept in one place so there is one
     * thing to audit rather than five string concatenations.
     */
    protected function path(string $base, string $relative): string
    {
        return rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relative;
    }

    protected function ensureDir(string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fs.create_failed', ['path' => $dir]));
        }
    }
}
