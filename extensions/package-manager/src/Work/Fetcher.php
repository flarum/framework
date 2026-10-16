<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use RuntimeException;
use ZipArchive;

/**
 * Gets one package's archive and unpacks it into the staging area.
 *
 * 🚨 Staging, never `vendor/`. Fetch is the long, failure-prone phase — a slow
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
        private string $stagingDir,
        private ?string $authPath = null,
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
                 * 🚨 Checked before anything is unpacked. A corrupted or
                 * substituted archive that reached the staging area would be
                 * applied by the next phase without further question — this is
                 * the only point where that can still be caught.
                 */
                if (! hash_equals($source['shasum'], (string) $actual)) {
                    throw new RuntimeException(
                        Lang::trans('fetch.checksum', ['package' => $package])
                    );
                }
            }

            $this->unzip($archive, $target, $package);
        } finally {
            @unlink($archive);
        }
    }

    private function download(string $url): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mw-');

        $handle = fopen($tmp, 'wb');
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_USERAGENT => 'The Extension Manager',
            CURLOPT_HTTPHEADER => $this->authHeaders($url),
        ]);

        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        // curl_close() has done nothing since PHP 8.0 and is deprecated in 8.5.
        // The handle is released when it goes out of scope.
        unset($ch);
        fclose($handle);

        if ($ok === false || $code >= 400) {
            @unlink($tmp);

            /*
             * 🚨 A 404 from a private repository means "not authenticated", not
             * "does not exist" — GitHub hides private resources behind 404
             * rather than 403. Saying so here saves the hour it otherwise costs
             * to work out that the commit is fine and the token is not.
             */
            $hint = $code === 404 && $this->authHeaders($url) === []
                ? ' '.Lang::trans('fetch.private_hint')
                : '';

            throw new RuntimeException(Lang::trans('fetch.download_failed', ['url' => $url, 'code' => (string) $code]).$hint.($err ? " $err" : ''));
        }

        return $tmp;
    }

    /**
     * @return list<string>
     */
    private function authHeaders(string $url): array
    {
        $auth = $this->auth();
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (($auth['github-oauth'] ?? []) as $h => $token) {
            if ($this->hostMatches($host, (string) $h)) {
                return ['Authorization: Bearer '.$token];
            }
        }

        foreach (($auth['bearer'] ?? []) as $h => $token) {
            if ($this->hostMatches($host, (string) $h)) {
                return ['Authorization: Bearer '.$token];
            }
        }

        foreach (($auth['http-basic'] ?? []) as $h => $cred) {
            if ($this->hostMatches($host, (string) $h) && isset($cred['username'], $cred['password'])) {
                return ['Authorization: Basic '.base64_encode($cred['username'].':'.$cred['password'])];
            }
        }

        return [];
    }

    private function hostMatches(string $host, string $configured): bool
    {
        $configured = strtolower($configured);

        // api.github.com and codeload.github.com both belong to a github.com entry.
        return $host === $configured || str_ends_with($host, '.'.$configured);
    }

    /** @return array<string,mixed> */
    private function auth(): array
    {
        if ($this->authPath === null || ! is_file($this->authPath)) {
            return [];
        }

        return (array) json_decode((string) file_get_contents($this->authPath), true);
    }

    /**
     * 🚨 Archives from GitHub and friends wrap everything in a single generated
     * top-level directory whose name nobody should depend on. It is stripped, so
     * what lands in staging is the package itself and the apply phase can move
     * it straight into place.
     */
    private function unzip(string $archive, string $target, string $package): void
    {
        $zip = new ZipArchive();

        if ($zip->open($archive) !== true) {
            throw new RuntimeException(Lang::trans('fetch.not_archive', ['package' => $package]));
        }

        $root = $this->commonRoot($zip);
        $this->deleteTree($target);

        if (! mkdir($target, 0775, true) && ! is_dir($target)) {
            throw new RuntimeException(Lang::trans('fetch.staging_failed', ['package' => $package]));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $rel = $root === null ? $name : substr($name, strlen($root));

            if ($rel === '' || str_ends_with($rel, '/')) {
                continue;
            }

            // 🚨 An archive is untrusted input, and "../" inside an entry name is
            // the oldest way out of a target directory there is.
            if (str_contains($rel, '..')) {
                throw new RuntimeException(Lang::trans('fetch.path_escapes', ['package' => $package]));
            }

            $to = $target.'/'.$rel;

            if (! is_dir(dirname($to)) && ! mkdir(dirname($to), 0775, true) && ! is_dir(dirname($to))) {
                throw new RuntimeException(Lang::trans('fs.create_failed', ['path' => dirname($to)]));
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                throw new RuntimeException(Lang::trans('fetch.read_entry_failed', ['name' => $name, 'package' => $package]));
            }

            file_put_contents($to, $stream);
            fclose($stream);
        }

        $zip->close();
    }

    /** The single wrapping directory, if every entry shares one. */
    private function commonRoot(ZipArchive $zip): ?string
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

    private function safePath(string $package): string
    {
        foreach (explode('/', $package) as $part) {
            if (! preg_match('/^[A-Za-z0-9._-]+$/', $part) || $part === '.' || $part === '..') {
                throw new RuntimeException("Refusing to stage a package named: $package");
            }
        }

        return $package;
    }

    private function deleteTree(string $dir): void
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
