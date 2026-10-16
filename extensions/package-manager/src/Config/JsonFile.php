<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Reading and writing the two files Composer will not let you get wrong twice.
 *
 * 🚨 composer.json and auth.json are the files that decide whether Composer can
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
    public function __construct(private string $path)
    {
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
            throw new RuntimeException(Lang::trans('config.json_unreadable', ['path' => $this->path]));
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            /*
             * 🚨 Refuse rather than treat it as empty. Returning [] here would
             * mean the next write replaces a file we could not understand with
             * one built from nothing — turning a file somebody could still fix
             * by hand into one whose contents are gone.
             */
            throw new RuntimeException(
                Lang::trans('config.json_invalid', ['file' => basename($this->path), 'error' => json_last_error_msg() ?: Lang::trans('config.unknown_error')])
            );
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $data
     * @param int $mode permissions for a file we are creating; 0600 for secrets
     */
    public function write(array $data, int $mode = 0664): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException(Lang::trans('config.json_encode_failed', ['file' => basename($this->path), 'error' => json_last_error_msg()]));
        }

        // 🚨 Prove it parses before it goes anywhere near the real path.
        if (json_decode($json, true) === null) {
            throw new RuntimeException(Lang::trans('config.json_refuse_write', ['file' => basename($this->path)]));
        }

        $this->backup();

        $tmp = $this->path.'.extension-manager-'.bin2hex(random_bytes(4));
        $handle = @fopen($tmp, 'w');

        if ($handle === false) {
            throw new RuntimeException(Lang::trans('config.json_write_failed', ['path' => $this->path]));
        }

        try {
            fwrite($handle, $json."\n");
            fflush($handle);
            // The file must be on disk before the rename, or a crash between the
            // two leaves an empty file where a valid one used to be.
            @fsync($handle);
        } finally {
            fclose($handle);
        }

        @chmod($tmp, $mode);

        if (! @rename($tmp, $this->path)) {
            @unlink($tmp);

            throw new RuntimeException(Lang::trans('config.json_replace_failed', ['path' => $this->path]));
        }
    }

    /**
     * Keep the previous contents, timestamped.
     *
     * 🚨 Beside the file rather than in a temp directory, so somebody debugging
     * a broken site at 2am finds them without being told where to look.
     */
    private function backup(): void
    {
        if (! $this->exists()) {
            return;
        }

        $to = $this->path.'.extension-manager-backup-'.date('Ymd-His');

        if (! is_file($to)) {
            @copy($this->path, $to);
        }
    }
}
