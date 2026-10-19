<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Flarum's own web updater, asked to run its migrations.
 *
 * The moment new core files land, the version in the code no longer matches the
 * one in the database, and Flarum answers every request (the forum, the API,
 * this extension's own step endpoint) with its "Update Flarum" page (503).
 * Found driving rc.8 → nightly through the web API, 2026-10-07: the run swapped
 * the files and could never reach its own migrations step again. The command
 * line is not gated, which is why the CLI test passed.
 *
 * That page has one way out, and it is Flarum's: POST the database credentials
 * from config.php and it runs `migrate`, in a fresh request with the new code.
 * On a host that cannot start a process, this asks the updater itself, from the
 * same request that swapped the files.
 *
 * The credentials themselves are never sent. The request carries a one-time
 * token instead (see UpdaterToken), which the receiving request exchanges for
 * the credentials on the server. The web server is asked directly on
 * 127.0.0.1 first, with the forum's host name, and the forum's own address is
 * used only when that fails and it is https.
 */
class FlarumUpdater
{
    protected const TIMEOUT = 300;

    /** @param array<string,mixed> $database config.php's `database` section */
    public function __construct(
        protected TranslatorInterface $translator,
        protected ClientInterface $http,
        protected string $siteUrl,
        protected array $database,
        protected string $storagePath,
    ) {
    }

    /** @return string what the updater said */
    public function migrate(): string
    {
        $host = (string) parse_url($this->siteUrl, PHP_URL_HOST);
        $port = parse_url($this->siteUrl, PHP_URL_PORT);
        $path = (string) (parse_url($this->siteUrl, PHP_URL_PATH) ?: '/');
        $loopback = 'http://127.0.0.1'.$path;
        $hostHeader = $host.($port ? ':'.$port : '');

        // Without a storage directory there is nowhere to keep a token.
        $local = $this->post($loopback, $hostHeader, $this->storagePath === '' ? UpdaterToken::fields($this->database) : null);

        if ($local['status'] === 200) {
            return $local['body'];
        }

        // Unreachable, or redirected to https (a site that forces it): ask the real address.
        $redirected = $local['status'] !== null && $local['status'] >= 300 && $local['status'] < 400;

        if (($local['status'] === null || $redirected) && str_starts_with($this->siteUrl, 'https://')) {
            $remote = $this->post($this->siteUrl, null);

            if ($remote['status'] === 200) {
                return $remote['body'];
            }

            $local = $remote;
        }

        /*
         * The updater did not take the token: a site whose config.php or
         * storage is not beside vendor/, where bootstrap/web-reset.php looks.
         * The credentials go to the web server on this machine and nowhere
         * else, never to the public address.
         */
        if ($this->storagePath !== '' && $local['status'] === 500 && str_contains($local['body'], 'Incorrect database credentials')) {
            $direct = $this->post($loopback, $hostHeader, UpdaterToken::fields($this->database));

            if ($direct['status'] === 200) {
                return $direct['body'];
            }

            $local = $direct['status'] === null ? $local : $direct;
        }

        throw new RuntimeException(
            $local['status'] === null
                ? $this->translator->trans('flarum-extension-manager.api.updater.unreachable', ['error' => $local['error']])
                : $this->translator->trans('flarum-extension-manager.api.updater.answered', ['status' => $local['status'], 'body' => mb_substr(trim(strip_tags($local['body'])), 0, 200)])
        );
    }

    /**
     * One POST to the updater: with a fresh one-time token, or, to the
     * loopback address only, with the credentials themselves.
     *
     * @param array<string,string>|null $credentials
     * @return array{status:int|null, body:string, error:string}
     */
    protected function post(string $url, ?string $hostHeader, ?array $credentials = null): array
    {
        $headers = ['User-Agent' => 'The Extension Manager'] + ($hostHeader !== null ? ['Host' => $hostHeader] : []);

        if ($credentials === null) {
            $headers[UpdaterToken::HEADER] = UpdaterToken::issue($this->storagePath);
        }

        try {
            $response = $this->http->request('POST', $url, [
                'form_params' => $credentials ?? [],
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'connect_timeout' => 8,
                'allow_redirects' => false,
                'http_errors' => false,
            ]);

            return ['status' => $response->getStatusCode(), 'body' => (string) $response->getBody(), 'error' => ''];
        } catch (GuzzleException $e) {
            return ['status' => null, 'body' => '', 'error' => $e->getMessage()];
        } finally {
            // Used or not, it is never left lying about.
            if ($credentials === null) {
                UpdaterToken::revoke($this->storagePath);
            }
        }
    }
}
