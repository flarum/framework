<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

use Flarum\ExtensionManager\Config\JsonFile;

/**
 * The command-line PHP an admin has pointed the Extension Manager at, for the hosts where
 * looking for one cannot work.
 *
 * 🚨 In storage, not in Flarum's settings table, like everything else
 * The Extension Manager keeps: the processes that read it run while vendor/ is being
 * replaced, and crash recovery reads it with nothing booted at all.
 *
 * 🚨 Validated when it is saved, by RUNNING it — not by is_file, which
 * open_basedir makes lie, and not by name, which says nothing about whether
 * the binary is the FPM one.
 */
class PhpOverride
{
    public function __construct(private string $storagePath)
    {
    }

    public function get(): ?string
    {
        try {
            $path = (new JsonFile($this->file()))->read()['path'] ?? null;
        } catch (\Throwable) {
            return null;
        }

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Why this path cannot be used, as a translation key and its parameters,
     * or null when it is a working command-line PHP.
     *
     * @return array{0:string, 1:array<string,string>}|null
     */
    public function refusal(string $path, ?PhpBinary $php = null): ?array
    {
        $php ??= new PhpBinary();

        if ($path === '' || $path[0] !== '/') {
            return ['php_path_not_absolute', []];
        }

        $probe = $php->probe($path);

        if ($probe['ok']) {
            return null;
        }

        if (! $probe['hidden'] && ! is_file($path)) {
            return ['php_path_missing', ['path' => $path]];
        }

        if ($probe['error'] === 'not executable') {
            return ['php_path_not_executable', ['path' => $path]];
        }

        if (! $probe['ran']) {
            return $probe['hidden']
                ? ['php_path_hidden_and_silent', ['path' => $path]]
                : ['php_path_did_not_run', ['path' => $path, 'error' => '']];
        }

        if ($probe['sapi'] !== null && $probe['sapi'] !== 'cli') {
            return ['php_path_not_cli', ['path' => $path, 'sapi' => (string) $probe['sapi']]];
        }

        return ['php_path_did_not_run', ['path' => $path, 'error' => (string) $probe['error']]];
    }

    public function set(?string $path): void
    {
        $dir = dirname($this->file());

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if ($path === null || $path === '') {
            @unlink($this->file());

            return;
        }

        (new JsonFile($this->file()))->write(['path' => $path]);
    }

    private function file(): string
    {
        return StorageDir::of($this->storagePath).'/php.json';
    }
}
