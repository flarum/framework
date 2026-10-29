<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\Locale\TranslatorInterface;
use RuntimeException;

/**
 * Reading and writing the two files Composer will not let you get wrong twice.
 *
 * composer.json and auth.json are the files that decide whether Composer can
 * run at all. A malformed composer.json does not degrade a site, it stops every
 * future install and update dead — including the one that would put it back. So
 * every write here is: validate, back up, write to a temporary file, fsync, and
 * rename into place.
 *
 * The rename is what makes it safe. A half-written composer.json is
 * unrecoverable from the admin screen that wrote it; rename(2) is atomic, so a
 * reader sees either the old file or the new one and never a partial one.
 */
class JsonFile
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected string $path,
    ) {
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /** @return array<string,mixed> */
    public function read(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $raw = @file_get_contents($this->path);

        if ($raw === false) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.config.json_unreadable', ['path' => $this->path]));
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            /*
             * Refuse rather than treat it as empty. Returning [] here would
             * mean the next write replaces a file we could not understand with
             * one built from nothing — turning a file somebody could still fix
             * by hand into one whose contents are gone.
             */
            throw new RefusedException(
                $this->translator->trans('flarum-extension-manager.api.config.json_invalid', ['file' => basename($this->path), 'error' => json_last_error_msg() ?: $this->translator->trans('flarum-extension-manager.api.config.unknown_error')])
            );
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $data
     * @param int $mode the most open the file may be; 0600 for secrets. An
     *                  existing file keeps its own mode where that is tighter.
     * @param bool $backup false for a file that holds secrets: a copy of one
     *                     is one more place for them to leak from
     */
    public function write(array $data, int $mode = 0664, bool $backup = true): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.config.json_encode_failed', ['file' => basename($this->path), 'error' => json_last_error_msg()]));
        }

        // Prove it parses before it goes anywhere near the real path.
        if (json_decode($json, true) === null) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.config.json_refuse_write', ['file' => basename($this->path)]));
        }

        if ($this->exists()) {
            $mode &= fileperms($this->path) & 0777;
        }

        $backup ? $this->backup($mode) : $this->removeBackups();

        $tmp = $this->path.'.extension-manager-'.bin2hex(random_bytes(4));
        $handle = @fopen($tmp, 'x');

        if ($handle === false) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.config.json_write_failed', ['path' => $this->path]));
        }

        // Before anything is written, so a secret is never in a file others can read.
        @chmod($tmp, $mode);

        try {
            fwrite($handle, $json."\n");
            fflush($handle);
            // The file must be on disk before the rename, or a crash between the
            // two leaves an empty file where a valid one used to be.
            @fsync($handle);
        } finally {
            fclose($handle);
        }

        if (! @rename($tmp, $this->path)) {
            @unlink($tmp);

            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.config.json_replace_failed', ['path' => $this->path]));
        }
    }

    /**
     * Keep the previous contents, once, with the file's own permissions.
     *
     * Beside the file rather than in a temp directory, so somebody debugging a
     * broken site at 2am finds it without being told where to look. One copy,
     * replaced by the next write, rather than one per write kept for ever.
     */
    protected function backup(int $mode): void
    {
        $this->removeBackups();

        if (! $this->exists()) {
            return;
        }

        $to = $this->path.'.extension-manager-backup';
        $tmp = $to.'.'.bin2hex(random_bytes(4));
        $handle = @fopen($tmp, 'x');

        if ($handle === false) {
            return;
        }

        @chmod($tmp, $mode);
        fwrite($handle, (string) @file_get_contents($this->path));
        fclose($handle);

        if (! @rename($tmp, $to)) {
            @unlink($tmp);
        }
    }

    /** Copies earlier versions kept, one per write, and the single copy for a file that no longer wants one. */
    protected function removeBackups(): void
    {
        foreach (glob($this->path.'.extension-manager-backup*') ?: [] as $old) {
            @unlink($old);
        }
    }
}
