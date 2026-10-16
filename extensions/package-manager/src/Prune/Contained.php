<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Prune;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Deleting things that live directly inside ONE directory, and nothing else.
 *
 * 🚨 This is the only code in the Extension Manager that deletes a rollback copy, so it is
 * written to be incapable of three things, each of which has happened somewhere:
 *
 *   1. **Walking through a symlink.** A link is one thing to remove, never a
 *      door. Composer installs path repositories as links into a checkout, so a
 *      walker that follows them empties somebody's working tree. Every entry is
 *      `lstat`ed and a link is unlinked as itself — at the top and at every
 *      level below.
 *   2. **Leaving the directory it was given.** A name is one path component,
 *      never `..`, never containing a slash, and every directory it descends
 *      into must still resolve to somewhere strictly inside the base. Anything
 *      else is refused with an exception, not skipped quietly.
 *   3. **Using RecursiveDirectoryIterator.** Its link handling depends on flags
 *      and PHP versions; a hand-rolled walk over `scandir` + `lstat` has no
 *      behaviour that is not on this page.
 */
class Contained
{
    private string $base;

    public function __construct(string $base)
    {
        $real = realpath($base);

        if ($real === false || ! is_dir($real)) {
            throw new RuntimeException(Lang::trans('prune.base_not_dir', ['path' => $base]));
        }

        $this->base = rtrim($real, DIRECTORY_SEPARATOR);
    }

    public function base(): string
    {
        return $this->base;
    }

    /**
     * Remove one top-level entry by name, returning the bytes it occupied.
     */
    public function remove(string $name): int
    {
        $path = $this->entry($name);

        return $this->removePath($path);
    }

    /**
     * Remove an entry given as a full path.
     *
     * 🚨 The path must be a DIRECT child of the base. Its parent is resolved
     * (so `trash/../vendor/x` is seen for what it is) but the entry itself is
     * not — resolving a link to decide whether to delete it would be asking the
     * link where it points, which is the one question that must not matter.
     */
    public function removeAt(string $path): int
    {
        $parent = realpath(dirname($path));

        if ($parent === false || rtrim($parent, DIRECTORY_SEPARATOR) !== $this->base) {
            throw new RuntimeException(Lang::trans('prune.not_inside', ['path' => $path, 'base' => $this->base]));
        }

        return $this->removePath($this->entry(basename($path)));
    }

    /** The bytes an entry occupies on disk, counted without following links. */
    public function size(string $name): int
    {
        return $this->measure($this->entry($name));
    }

    /**
     * When an entry last changed, as far as the filesystem will say.
     *
     * 🚨 The LATER of mtime and ctime. A rename keeps a directory's mtime — a
     * package moved to the trash today still says when Composer extracted it,
     * months ago — but it does update ctime, which is the moment it arrived.
     * Taking the later of the two means a copy is never judged older than it is.
     */
    public function changedAt(string $name): int
    {
        $stat = @lstat($this->entry($name));

        return $stat === false ? 0 : max((int) $stat['mtime'], (int) $stat['ctime']);
    }

    /** @return list<string> the names directly inside the base */
    public function names(): array
    {
        $names = @scandir($this->base);

        if ($names === false) {
            throw new RuntimeException(Lang::trans('prune.cannot_list', ['path' => $this->base]));
        }

        return array_values(array_filter($names, fn ($n) => $n !== '.' && $n !== '..'));
    }

    private function entry(string $name): string
    {
        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '/')
            || str_contains($name, '\\') || str_contains($name, "\0")) {
            throw new RuntimeException(Lang::trans('prune.not_single_name', ['name' => $name, 'base' => $this->base]));
        }

        return $this->base.DIRECTORY_SEPARATOR.$name;
    }

    private function removePath(string $path): int
    {
        $stat = @lstat($path);

        if ($stat === false) {
            return 0;
        }

        if (is_link($path) || ! is_dir($path)) {
            $bytes = $this->bytes($stat);

            if (! @unlink($path) && (is_link($path) || file_exists($path))) {
                throw new RuntimeException(Lang::trans('prune.remove_failed', ['path' => $path]));
            }

            return $bytes;
        }

        return $this->removeDir($path);
    }

    private function removeDir(string $dir): int
    {
        $this->assertInside($dir);

        $bytes = 0;

        foreach (@scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$name;
            $stat = @lstat($path);

            if ($stat === false) {
                continue;
            }

            // 🚨 is_link FIRST: is_dir() is true for a link to a directory.
            if (is_link($path) || ! is_dir($path)) {
                $bytes += $this->bytes($stat);
                @unlink($path);

                continue;
            }

            $bytes += $this->removeDir($path);
        }

        $stat = @lstat($dir);
        $bytes += $stat === false ? 0 : $this->bytes($stat);

        if (! @rmdir($dir) && is_dir($dir)) {
            throw new RuntimeException(Lang::trans('prune.remove_dir_failed', ['path' => $dir]));
        }

        return $bytes;
    }

    private function measure(string $path): int
    {
        $stat = @lstat($path);

        if ($stat === false) {
            return 0;
        }

        if (is_link($path) || ! is_dir($path)) {
            return $this->bytes($stat);
        }

        $bytes = $this->bytes($stat);

        foreach (@scandir($path) ?: [] as $name) {
            if ($name !== '.' && $name !== '..') {
                $bytes += $this->measure($path.DIRECTORY_SEPARATOR.$name);
            }
        }

        return $bytes;
    }

    /**
     * 🚨 Checked on every directory before anything inside it is touched. The
     * walk never goes through a link, so this cannot fail in practice — which
     * is exactly why it is here: if it ever does, something about the tree is
     * not what this class assumes, and stopping is the only safe response.
     */
    private function assertInside(string $dir): void
    {
        $real = realpath($dir);

        if ($real === false || ! str_starts_with($real, $this->base.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException(Lang::trans('prune.resolves_outside', ['path' => $dir, 'base' => $this->base]));
        }
    }

    /** @param array<string|int,int> $stat */
    private function bytes(array $stat): int
    {
        // Blocks, like du, when the platform reports them; size otherwise.
        $blocks = (int) ($stat['blocks'] ?? -1);

        return $blocks >= 0 ? $blocks * 512 : (int) $stat['size'];
    }
}
