<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

/**
 * A one-time token that stands in for the database credentials when a core
 * update asks Flarum's own updater to migrate.
 *
 * The updater only accepts config.php's database credentials. Rather than
 * send them, possibly out through a CDN and back, the request carries a random
 * token whose hash is kept under storage/. bootstrap/web-reset.php, which runs
 * before Flarum handles the request, redeems it and hands the updater the
 * credentials from config.php on the server itself. A token works once, for a
 * minute, and only the server ever holds the credentials.
 */
class UpdaterToken
{
    /** A fixed name the framework export leaves as it is, so every copy sends and reads the same header. */
    public const HEADER = 'X-Extension-Manager-Token';

    /** The same header, as PHP presents it in $_SERVER. */
    public const SERVER_KEY = 'HTTP_X_EXTENSION_MANAGER_TOKEN';

    protected const TTL = 60;

    public static function issue(string $storagePath): string
    {
        $token = bin2hex(random_bytes(32));
        $file = self::path($storagePath);

        if (! is_dir(dirname($file))) {
            @mkdir(dirname($file), 0775, true);
        }

        $tmp = $file.'.'.bin2hex(random_bytes(4));
        $written = @file_put_contents($tmp, (string) json_encode(['hash' => hash('sha256', $token), 'expires' => time() + self::TTL]));
        @chmod($tmp, 0600);

        if ($written === false || ! @rename($tmp, $file)) {
            @unlink($tmp);
        }

        return $token;
    }

    /** True once, for the token last issued, within its minute. */
    public static function redeem(string $storagePath, string $token): bool
    {
        $file = self::path($storagePath);
        $data = json_decode((string) @file_get_contents($file), true);

        if (! is_array($data) || ! is_string($data['hash'] ?? null) || (int) ($data['expires'] ?? 0) < time()
            || ! hash_equals($data['hash'], hash('sha256', $token))) {
            return false;
        }

        // Claimed by a rename, so two requests carrying it cannot both succeed.
        $claimed = $file.'.'.getmypid().'.'.bin2hex(random_bytes(3));

        if (! @rename($file, $claimed)) {
            return false;
        }

        @unlink($claimed);

        return true;
    }

    public static function revoke(string $storagePath): void
    {
        @unlink(self::path($storagePath));
    }

    /**
     * Called from bootstrap/web-reset.php on a request that carries the
     * header: a POST with a valid token gets config.php's credentials as the
     * form fields Flarum's updater checks.
     */
    public static function honour(string $basePath, string $storagePath): void
    {
        $token = $_SERVER[self::SERVER_KEY] ?? null;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ! is_string($token) || $token === ''
            || ! is_file($basePath.'/config.php') || ! self::redeem($storagePath, $token)) {
            return;
        }

        $config = include $basePath.'/config.php';
        $_POST = self::fields(is_array($config) ? (array) ($config['database'] ?? []) : []);
    }

    /**
     * What Flarum's updater checks: the username and password, or on SQLite,
     * which has neither, the database file's name.
     *
     * @param array<string,mixed> $database config.php's `database` section
     * @return array<string,string>
     */
    public static function fields(array $database): array
    {
        $username = (string) ($database['username'] ?? '');
        $password = (string) ($database['password'] ?? '');

        return $username === '' && $password === ''
            ? ['databaseName' => (string) ($database['database'] ?? '')]
            : ['databaseUsername' => $username, 'databasePassword' => $password];
    }

    protected static function path(string $storagePath): string
    {
        return StorageDir::of($storagePath).'/updater-token';
    }
}
