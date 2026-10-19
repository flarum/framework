<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use ZipArchive;

/**
 * Gets one package's archive and unpacks it into the staging area.
 *
 * Staging, never `vendor/`. Fetch is the long, failure-prone phase — a slow
 * mirror, a 404, a host that cuts the request — and none of that is allowed
 * anywhere near the tree the site is running from. By the time apply starts,
 * every file is already on disk and verified, so the only thing left is two
 * renames that cannot fail halfway.
 *
 * One package per call, because the driver's whole promise is that no single
 * request needs to be long.
 */
class Fetcher
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected ClientInterface $http,
        protected string $stagingDir,
        protected ?string $authPath = null,
    ) {
    }

    /**
     * @param array{url:string,type:string,reference:?string,shasum:?string} $source
     */
    public function fetch(string $package, array $source): void
    {
        $target = $this->stagingDir.'/'.$this->safePath($package);

        if (is_dir($target) && is_file($target.'/composer.json')) {
            // Already staged by an earlier attempt. Fetch is idempotent because
            // the driver saves progress AFTER the work, so a process killed in
            // between will ask for this package again.
            return;
        }

        $archive = $this->download($source['url']);

        try {
            if ($source['shasum'] !== null) {
                $actual = hash_file('sha1', $archive);

                /*
                 * Checked before anything is unpacked. A corrupted or
                 * substituted archive that reached the staging area would be
                 * applied by the next phase without further question — this is
                 * the only point where that can still be caught.
                 */
                if (! hash_equals($source['shasum'], (string) $actual)) {
                    throw new RuntimeException(
                        $this->translator->trans('flarum-extension-manager.api.fetch.checksum', ['package' => $package])
                    );
                }
            }

            $this->unzip($archive, $target, $package);
        } finally {
            @unlink($archive);
        }
    }

    protected function download(string $url): string
    {
        /*
         * Composer's own rule (secure-http): an archive is fetched over HTTPS
         * or not at all, and a redirect may not leave HTTPS either.
         */
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.not_https', ['url' => $url]));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'mw-');
        $headers = $this->authHeaders($url);
        $code = 0;
        $err = '';

        try {
            $code = $this->http->request('GET', $url, [
                'sink' => $tmp,
                'timeout' => 300,
                'connect_timeout' => 20,
                'http_errors' => false,
                /*
                 * Guzzle drops the Authorization header when a redirect leaves
                 * the origin, as GitHub's api.github.com -> codeload.github.com
                 * does, so a token is only ever sent to the host it is for.
                 */
                'allow_redirects' => ['max' => 5, 'protocols' => ['https'], 'referer' => false],
                'headers' => ['User-Agent' => 'The Extension Manager'] + $headers,
            ])->getStatusCode();
        } catch (GuzzleException $e) {
            $err = $e->getMessage();
        }

        if ($code === 0 || $code >= 400) {
            @unlink($tmp);

            /*
             * A 404 from a protected repository means "not authenticated", not
             * "does not exist": GitHub hides protected resources behind 404
             * rather than 403. Saying so saves the hour it otherwise costs to
             * work out that the commit is fine and the token is not.
             */
            $hint = $code === 404 && $headers === []
                ? ' '.$this->translator->trans('flarum-extension-manager.api.fetch.private_hint')
                : '';

            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.download_failed', ['url' => $url, 'code' => (string) $code]).$hint.($err ? " $err" : ''));
        }

        return $tmp;
    }

    /**
     * The credential for exactly this URL's host; see AuthTokens::authorization().
     *
     * @return array<string,string>
     */
    protected function authHeaders(string $url): array
    {
        $authorization = AuthTokens::authorization($this->auth(), $url);

        return $authorization === null ? [] : ['Authorization' => $authorization];
    }

    /** @return array<string,mixed> */
    protected function auth(): array
    {
        if ($this->authPath === null || ! is_file($this->authPath)) {
            return [];
        }

        return (array) json_decode((string) file_get_contents($this->authPath), true);
    }

    /**
     * Archives from GitHub and friends wrap everything in a single generated
     * top-level directory whose name nobody should depend on. It is stripped, so
     * what lands in staging is the package itself and the apply phase can move
     * it straight into place.
     */
    protected function unzip(string $archive, string $target, string $package): void
    {
        $zip = new ZipArchive();

        if ($zip->open($archive) !== true) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.not_archive', ['package' => $package]));
        }

        $root = $this->commonRoot($zip);
        $this->deleteTree($target);

        if (! mkdir($target, 0775, true) && ! is_dir($target)) {
            throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.staging_failed', ['package' => $package]));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $rel = $root === null ? $name : substr($name, strlen($root));

            if ($rel === '' || str_ends_with($rel, '/')) {
                continue;
            }

            // An archive is untrusted input, and "../" inside an entry name is
            // the oldest way out of a target directory there is.
            if (str_contains($rel, '..')) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.path_escapes', ['package' => $package]));
            }

            $to = $target.'/'.$rel;

            if (! is_dir(dirname($to)) && ! mkdir(dirname($to), 0775, true) && ! is_dir(dirname($to))) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fs.create_failed', ['path' => dirname($to)]));
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                throw new RuntimeException($this->translator->trans('flarum-extension-manager.api.fetch.read_entry_failed', ['name' => $name, 'package' => $package]));
            }

            file_put_contents($to, $stream);
            fclose($stream);
        }

        $zip->close();
    }

    /** The single wrapping directory, if every entry shares one. */
    protected function commonRoot(ZipArchive $zip): ?string
    {
        $root = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $slash = strpos($name, '/');

            if ($slash === false) {
                return null;                       // a file at the top level
            }

            $first = substr($name, 0, $slash + 1);

            if ($root === null) {
                $root = $first;
            } elseif ($root !== $first) {
                return null;                       // more than one top-level entry
            }
        }

        return $root;
    }

    protected function safePath(string $package): string
    {
        foreach (explode('/', $package) as $part) {
            if (! preg_match('/^[A-Za-z0-9._-]+$/', $part) || $part === '.' || $part === '..') {
                throw new RuntimeException("Refusing to stage a package named: $package");
            }
        }

        return $package;
    }

    protected function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }
}
