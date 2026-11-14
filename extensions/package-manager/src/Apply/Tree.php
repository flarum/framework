<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Apply;

/**
 * Removing and moving a directory, done once.
 *
 * One copy on purpose. The applier and the rollback each had their own, and
 * they drifted: the symlink hole was fixed in one and left open in the other, so
 * a rollback could still reach through a link into a working checkout and empty
 * it. Two implementations of a dangerous operation is two chances to fix it
 * incompletely.
 */
class Tree
{
    /**
     * Never walks through a symlink.
     *
     * RecursiveDirectoryIterator follows them by default, and is_dir() is true
     * for a link pointing at a directory — so the obvious version of this
     * descends through the link and unlinks the files on the far side. Composer
     * installs a path repository AS A SYMLINK, so the far side is a checkout
     * somebody is editing.
     *
     * A link is one thing to remove, never a door.
     */
    public static function delete(string $dir): void
    {
        if (is_link($dir)) {
            @unlink($dir);

            return;
        }

        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $path = $item->getPathname();

            if (is_link($path)) {
                @unlink($path);

                continue;
            }

            $item->isDir() ? @rmdir($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    /** Left in a copied tree until its source is gone; see move(). */
    public const COPIED = '.extension-manager-copied';

    /**
     * rename(), and where the filesystem refuses one, copy then delete.
     *
     * Docker's overlay filesystem refuses to rename a directory that came
     * from the IMAGE (EXDEV, "Cross-device link") — and in an image with
     * Flarum baked in, that is every package in vendor/. Found on
     * Flarum-in-a-box, 2026-10-07: no update could move the installed version
     * aside, so the Extension Manager could update nothing there at all. A directory
     * created at runtime renames normally, so only the first move of an
     * image's package ever takes this path.
     *
     * Not atomic, so it is made resumable instead; see copyMove().
     */
    public static function move(string $from, string $to): bool
    {
        if (@rename($from, $to)) {
            return true;
        }

        if (! self::crossDevice(error_get_last()['message'] ?? '')) {
            return false;
        }

        return self::copyMove($from, $to);
    }

    /**
     * Whether a failed rename() failed because source and target are on
     * different filesystems (EXDEV). glibc says "Invalid cross-device link"
     * and musl "Cross-device link", so the match ignores case.
     */
    public static function crossDevice(string $error): bool
    {
        return stripos($error, 'cross-device') !== false;
    }

    /**
     * The copy half of move(), for a rename the filesystem refused.
     *
     * The copy is built under partial() and renamed into place only
     * once it is whole, with the COPIED marker already inside it, so a target
     * that exists is always complete. Then the source is deleted, then the
     * marker. Killed mid-copy: there is no target, only a partial that the
     * next attempt clears, and the source is untouched. Killed mid-delete: the
     * marker says the copy is whole, and finishMove() completes it instead of
     * trusting the half-deleted source.
     */
    public static function copyMove(string $from, string $to): bool
    {
        if (! is_dir($from) || is_link($from) || file_exists($to)) {
            return false;
        }

        $partial = self::partial($to);
        self::delete($partial);

        if (! self::copy($from, $partial) || @file_put_contents($partial.'/'.self::COPIED, '') === false || ! @rename($partial, $to)) {
            self::delete($partial);

            return false;
        }

        self::delete($from);
        @unlink($to.'/'.self::COPIED);

        return ! file_exists($from);
    }

    /**
     * Where a directory is built before it is whole: beside its target, under
     * a name no package can have. `<target>.partial` could: Composer allows
     * dots, so the scratch copy of acme/foo was the real home of a package
     * named acme/foo.partial, and clearing one deleted the other. A package
     * name starts with a letter or digit, never a dot.
     */
    public static function partial(string $to): string
    {
        return dirname($to).DIRECTORY_SEPARATOR.'.'.basename($to).'.partial';
    }

    /**
     * A move() killed after its copy was whole: delete what is left of the
     * source and drop the marker. True when there was one to finish.
     */
    public static function finishMove(string $from, string $to): bool
    {
        if (! is_file($to.'/'.self::COPIED)) {
            return false;
        }

        self::delete($from);
        @unlink($to.'/'.self::COPIED);

        return true;
    }

    /** A link is copied as a link, never followed — see delete(). */
    protected static function copy(string $from, string $to): bool
    {
        if (is_link($from)) {
            $target = readlink($from);

            return $target !== false && @symlink($target, $to);
        }

        // Modes and modification times kept, so the copy reads to PHP and
        // to Composer exactly as the original did.
        if (! is_dir($from)) {
            return @copy($from, $to) && @chmod($to, fileperms($from) & 0777) && @touch($to, filemtime($from));
        }

        if (! @mkdir($to, fileperms($from) & 0777)) {
            return false;
        }

        foreach (scandir($from) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && ! self::copy($from.'/'.$entry, $to.'/'.$entry)) {
                return false;
            }
        }

        return true;
    }
}
