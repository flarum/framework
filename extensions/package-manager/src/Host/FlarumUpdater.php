<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Flarum's own web updater, asked to run its migrations.
 *
 * 🚨 The moment new core files land, the version in the code no longer matches
 * the one in the database, and Flarum answers EVERY request — the forum, the
 * API, the Extension Manager's own step endpoint — with its "Update Flarum" page (503).
 * Found driving rc.8 → nightly through the web API, 2026-10-07: the run swapped
 * the files and could never reach its own migrations step again. The command
 * line is not gated, which is why the CLI test passed.
 *
 * That page has one way out, and it is Flarum's: POST the database credentials
 * from config.php and it runs `migrate`, in a fresh request with the new code.
 * The Extension Manager knows those credentials — it runs inside the forum — so on a host
 * that cannot start a process it asks the updater itself, from the same
 * request that swapped the files.
 *
 * The credentials stay on the machine: the web server is asked directly on
 * 127.0.0.1 first, with the forum's host name, and the forum's own address is
 * used only when that fails AND it is https.
 */
final class FlarumUpdater
{
    private const TIMEOUT = 300;

    /** @param array<string,mixed> $database config.php's `database` section */
    public function __construct(private string $siteUrl, private array $database)
    {
    }

    /** @return string what the updater said */
    public function migrate(): string
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException(Lang::trans('updater.no_http'));
        }

        $username = (string) ($this->database['username'] ?? '');
        $password = (string) ($this->database['password'] ?? '');
        $fields = $username === '' && $password === ''
            ? ['databaseName' => (string) ($this->database['database'] ?? '')]
            : ['databaseUsername' => $username, 'databasePassword' => $password];

        $host = (string) parse_url($this->siteUrl, PHP_URL_HOST);
        $port = parse_url($this->siteUrl, PHP_URL_PORT);
        $path = (string) (parse_url($this->siteUrl, PHP_URL_PATH) ?: '/');

        $local = $this->post('http://127.0.0.1'.$path, $fields, $host.($port ? ':'.$port : ''));

        if ($local['status'] === 200) {
            return $local['body'];
        }

        // Unreachable, or redirected to https (a site that forces it): ask the real address.
        $redirected = $local['status'] !== null && $local['status'] >= 300 && $local['status'] < 400;

        if (($local['status'] === null || $redirected) && str_starts_with($this->siteUrl, 'https://')) {
            $remote = $this->post($this->siteUrl, $fields, null);

            if ($remote['status'] === 200) {
                return $remote['body'];
            }

            $local = $remote;
        }

        throw new RuntimeException(
            $local['status'] === null
                ? Lang::trans('updater.unreachable', ['error' => $local['error']])
                : Lang::trans('updater.answered', ['status' => $local['status'], 'body' => mb_substr(trim(strip_tags($local['body'])), 0, 200)])
        );
    }

    /**
     * @param array<string,string> $fields
     * @return array{status:int|null, body:string, error:string}
     */
    private function post(string $url, array $fields, ?string $hostHeader): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'The Extension Manager',
            CURLOPT_HTTPHEADER => $hostHeader !== null ? ['Host: '.$hostHeader] : [],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        return [
            'status' => $body === false || $status === 0 ? null : $status,
            'body' => is_string($body) ? $body : '',
            'error' => curl_error($ch),
        ];
    }
}
