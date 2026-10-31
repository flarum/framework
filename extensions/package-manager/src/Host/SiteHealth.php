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

/**
 * Does the site still answer?
 *
 * Over HTTP, as a visitor, not by booting Flarum in this process.
 *
 * The failure this exists to catch is a site that will not BOOT — an extension
 * whose extend.php throws, a class the autoloader cannot find, a migration that
 * left the database disagreeing with the code. A check that runs inside an
 * already-booted process cannot see any of that: this process booted before the
 * change, and asking it whether booting works is asking the one witness who
 * cannot know.
 *
 * So: a real request, over the network, to the site's own address, answered by
 * a worker that booted after the change. That is the only thing that proves it.
 */
class SiteHealth
{
    /** Long enough for a cold boot on a small host, short enough not to hang a run. */
    protected const TIMEOUT = 15;

    public function __construct(
        protected TranslatorInterface $translator,
        protected ClientInterface $http,
        protected string $url,
    ) {
    }

    /**
     * @return array{ok:bool, status:int|null, why:string}
     */
    public function check(int $attempts = 3): array
    {
        $last = ['ok' => false, 'status' => null, 'why' => $this->translator->trans('flarum-extension-manager.api.health.never_reached')];

        for ($i = 0; $i < max(1, $attempts); $i++) {
            /*
             * A fresh query string every attempt. Between this extension, a
             * CDN and Flarum's own response cache there are several places a
             * 200 from before the update could be served from, and a cached
             * success is the one answer that would make this check worse than
             * not having it.
             */
            $last = $this->once($this->url.(str_contains($this->url, '?') ? '&' : '?')
                .'extension-manager-health='.bin2hex(random_bytes(6)));
            unset($last['body']);   // read for the loopback check, never passed on

            if ($last['ok']) {
                return $last;
            }

            /*
             * A short pause, because php-fpm workers do not all pick up new
             * code in the same instant and one unlucky request is not an
             * outage. Three tries over a few seconds is: they cannot all be
             * unlucky.
             */
            if ($i < $attempts - 1) {
                sleep(2);
            }
        }

        return $last;
    }

    /**
     * @return array{ok:bool, status:int|null, why:string, body?:string}
     */
    protected function once(string $url): array
    {
        $direct = $this->request($url);

        if ($direct['status'] !== null) {
            return $direct;
        }

        /*
         * Unreachable at its own address: ask the web server on this
         * machine directly, naming the site in the Host header.
         *
         * Inside a container the forum's address is the HOST's — localhost:8080
         * on Flarum-in-a-box, 127.0.0.1:8299 behind a port mapping — and from in
         * here nothing listens there, while the web server answering the
         * forum's requests is on port 80 of this very container. Every Docker
         * install measured on 2026-10-07 had the check switched off this way,
         * and with it the automatic rollback.
         *
         * Only when the direct request could not CONNECT, so a site that
         * answers normally is never second-guessed. And a loopback answer only
         * counts as healthy when it names this forum, since port 80 here may
         * belong to another site entirely.
         */
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/').(($q = parse_url($url, PHP_URL_QUERY)) ? '?'.$q : '');

        if ($host === '') {
            return $direct;
        }

        $local = $this->request('http://127.0.0.1'.$path, $host.($port ? ':'.$port : ''));

        if ($local['status'] === null) {
            return $direct;
        }

        if ($local['ok'] && ! str_contains($local['body'], $host)) {
            return $direct;
        }

        return ['ok' => $local['ok'], 'status' => $local['status'], 'why' => $local['why'].' '.$this->translator->trans('flarum-extension-manager.api.health.asked_directly')];
    }

    /**
     * @return array{ok:bool, status:int|null, why:string, body?:string}
     */
    protected function request(string $url, ?string $hostHeader = null): array
    {
        try {
            $response = $this->http->request('GET', $url, [
                'timeout' => self::TIMEOUT,
                'connect_timeout' => 8,
                'http_errors' => false,
                // A redirect is a working site, and following one only adds ways to fail.
                'allow_redirects' => false,
                'headers' => ['User-Agent' => 'The Extension Manager health check', 'Cache-Control' => 'no-cache', 'Pragma' => 'no-cache']
                    + ($hostHeader !== null ? ['Host' => $hostHeader] : []),
            ]);
        } catch (GuzzleException $e) {
            /*
             * Could not connect, or no way to make a request at all. Reported
             * as unusable, never as healthy: the caller compares this answer
             * before and after the update, and "cannot check" is a perfectly
             * good answer as long as it is the same one both times.
             */
            return ['ok' => false, 'status' => null, 'why' => $this->translator->trans('flarum-extension-manager.api.health.no_answer', ['error' => $e->getMessage() ?: $this->translator->trans('flarum-extension-manager.api.health.no_response')])];
        }

        $status = $response->getStatusCode();

        /*
         * Only 5xx is a broken site. A 403 or a 404 is a site making a
         * decision, which needs a working boot to make, and a protected forum
         * answering 403 to a signed-out request is not an outage, it is that
         * forum working exactly as configured.
         */
        if ($status >= 500) {
            return ['ok' => false, 'status' => $status, 'why' => $this->translator->trans('flarum-extension-manager.api.health.answered', ['status' => (string) $status])];
        }

        return ['ok' => true, 'status' => $status, 'why' => $this->translator->trans('flarum-extension-manager.api.health.answered', ['status' => (string) $status]), 'body' => (string) $response->getBody()];
    }
}
