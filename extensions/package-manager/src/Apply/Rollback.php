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
 * Undoes an apply by reading its journal backwards.
 *
 * It must work on a journal whose last entry is `begun` and not `done` —
 * that is the whole reason the journal is written before the act. That entry
 * describes a change that may have happened fully, partly, or not at all, and
 * the on-disk state is the only authority. So every step here is written to be
 * true whichever it was:
 *
 *   - restoring from the trash is skipped if the trash slot is empty
 *   - removing an added package is skipped if it is not there
 *   - a live directory is moved aside rather than deleted, even during a
 *     rollback, so a mistake here is still recoverable by hand
 *
 * Cost is proportional to the change, not the tree. Rolling back a
 * one-extension update moves one directory, which is why this works on a host
 * with a disk quota where copying vendor would not.
 */
class Rollback
{
    /**
     * @param ?string $installPath the forum root, when the manifests should be
     *        put back too. Optional so the recovery tests can exercise the
     *        filesystem half on a bare tree with no Composer project in it.
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected string $vendorDir,
        protected string $trashDir,
        protected Journal $journal,
        protected ?string $installPath = null,
        protected ?string $savedManifests = null,
    ) {
    }

    /**
     * @return list<string> what was undone, newest first
     */
    public function run(): array
    {
        /*
         * No early return on an empty journal.
         *
         * An empty journal means no FILES were moved. It does not mean nothing
         * changed: the plan phase runs Composer with --no-install, and Composer
         * rewrites composer.json and composer.lock the moment it succeeds —
         * before the journal has a single entry. A run that fails after that
         * point leaves the manifests describing a site that does not exist, and
         * returning early here left them that way while reporting that there
         * was nothing to undo.
         */
        $entries = $this->journal->entries();
        $undone = [];

        // Backwards: the last thing done is the first thing undone.
        foreach (array_reverse($entries) as $entry) {
            if (! isset($entry['change'])) {
                continue;
            }

            $change = Change::fromArray((array) $entry['change']);
            $trash = (string) ($entry['trash'] ?? $change->trashName());

            match ($change->op) {
                Change::REPLACE => $this->restore($change, $trash),
                Change::ADD => $this->uninstall($change),
                Change::REMOVE => $this->restore($change, $trash),
                default => throw new RuntimeException("Unknown change \"{$change->op}\" in the journal for {$change->package}"),
            };

            $undone[] = $change->describe($this->translator);
        }

        foreach ($this->restoreManifests() as $line) {
            $undone[] = $line;
        }

        return $undone;
    }

    /**
     * Put composer.lock — and composer.json, if installing changed it — back.
     *
     * Without this a rollback is only half a rollback, and the half it leaves
     * out is the half that misleads. The plan phase runs `composer update
     * --no-install`, which rewrites the lock immediately; moving the files back
     * afterwards while leaving the lock claiming the new versions gives a site
     * whose record of itself is wrong. Nothing looks broken. The damage appears
     * at the NEXT update, when Composer diffs from a lock that says work was
     * done that was undone, and plans around packages it believes are already in
     * place.
     *
     * Copied rather than renamed, so a failure part-way leaves the saved copy
     * intact and this can simply be run again.
     *
     * @return list<string>
     */
    protected function restoreManifests(): array
    {
        if ($this->installPath === null || $this->savedManifests === null) {
            return [];
        }

        $done = [];

        foreach (['composer.lock', 'composer.json'] as $file) {
            $saved = $this->savedManifests.'/'.$file.'.before';

            if (! is_file($saved)) {
                // composer.json is only saved when the run was an install, so
                // its absence is the normal case rather than a problem.
                continue;
            }

            if (! @copy($saved, $this->installPath.'/'.$file)) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.rollback.put_back_failed', ['file' => $file, 'from' => $saved]));
            }

            $done[] = $this->translator->trans('flarum-extension-manager.api.rollback.restored', ['file' => $file]);
        }

        return $done;
    }

    /** Put the stashed version back, discarding whatever is live. */
    protected function restore(Change $change, string $trashName): void
    {
        $live = $this->path($this->vendorDir, $change->relativePath());
        $stash = $this->path($this->trashDir, $trashName);

        if (! is_dir($stash)) {
            /*
             * The interrupted case: the process died before the stash, so the
             * live copy is still the original and there is nothing to restore.
             * Doing nothing is correct — and touching the live directory here
             * would destroy the very thing being recovered.
             */
            return;
        }

        if (is_dir($live)) {
            // The new version. Moved aside, not deleted — see the class comment.
            $aside = $this->path($this->trashDir, $change->trashName().'.rolledback');
            Tree::delete($aside);

            if (! Tree::move($live, $aside)) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.rollback.move_aside_failed', ['path' => $live]));
            }
        }

        $this->ensureDir(dirname($live));

        if (! Tree::move($stash, $live)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.rollback.restore_failed', ['from' => $stash, 'to' => $live]));
        }
    }

    /** Take out a package this apply had added. */
    protected function uninstall(Change $change): void
    {
        $live = $this->path($this->vendorDir, $change->relativePath());

        if (! is_dir($live)) {
            // Never installed — the apply died before this step.
            return;
        }

        $aside = $this->path($this->trashDir, $change->trashName().'.rolledback');
        Tree::delete($aside);
        $this->ensureDir(dirname($aside));

        if (! Tree::move($live, $aside)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.rollback.remove_failed', ['path' => $live]));
        }

        $this->tidyVendorDir(dirname($live));
    }

    /**
     * Remove the vendor namespace directory if this was the last package in it.
     *
     * Only when empty, and never above the vendor directory itself. rmdir
     * refuses a non-empty directory, so the check and the act cannot disagree
     * even if something is written between them.
     *
     * Cosmetic, and worth doing anyway: a rollback that promises to put
     * everything back the way it was should not leave an empty vendor/acme/
     * behind as evidence that it did not quite.
     */
    protected function tidyVendorDir(string $dir): void
    {
        $vendor = rtrim($this->vendorDir, DIRECTORY_SEPARATOR);

        if ($dir === $vendor || ! str_starts_with($dir, $vendor.DIRECTORY_SEPARATOR)) {
            return;
        }

        @rmdir($dir);
    }

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
