<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\Repositories;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Versions for packages Packagist has never heard of.
 *
 * This exists because the check was silently useless for exactly the
 * extensions somebody paid for.
 *
 * `extension-manager:check` asks Packagist about every installed extension. A premium
 * extension is not on Packagist — it comes from a protected Composer repository —
 * so it came back `uncheckable` and the forum was never told a new version
 * existed. On Ernest's two sites that was 7 and 9 packages, and they were the
 * paid ones.
 *
 * A protected Composer repository is just `packages.json` at a URL, which is the
 * same shape Composer itself reads. So the fix is not special-cased to anybody:
 * whatever `composer` repositories the site has configured get asked, with
 * whatever credentials the site already has for them.
 *
 * It fetches each repository ONCE per check, not once per package. A forum
 * with nine protected extensions would otherwise be nine downloads of the same
 * index, on a schedule, for one question.
 */
class PrivateIndex
{
    /** @var array<string, array<string, list<string>>|null> url => index, or null if it could not be read */
    protected array $indexes = [];

    /** @var array<string, list<string>|null> owner/repo => tags */
    protected array $tagLists = [];

    /** @var array<string, string|null> vcs url => the package it publishes */
    protected array $packageNames = [];

    /** @var array<string, array<string,string>> package => version => release URL */
    protected array $releaseNotes = [];

    public function __construct(
        protected Repositories $repositories,
        protected AuthTokens $auth,
        protected ClientInterface $http,
        protected int $timeout = 15,
    ) {
    }

    /**
     * Every version of $package any configured protected repository offers.
     *
     * @return list<string>|null null when no repository knows the package —
     *                           which keeps it honestly "uncheckable" rather
     *                           than implying it is up to date
     */
    public function versionsFor(string $package): ?array
    {
        $found = [];

        foreach ($this->composerRepositories() as $url) {
            $index = $this->index($url);

            if ($index === null || ! isset($index[$package])) {
                continue;
            }

            foreach ($index[$package] as $version) {
                $found[$version] = true;
            }
        }

        /*
         * Then the GitHub repositories, because that is how a self-published
         * extension is usually installed.
         *
         * Ernest's own two forums install his premium extensions from `vcs`
         * repositories pointing at GitHub, not from a Composer index — so the
         * index above, which is what a CUSTOMER uses, would have left his own
         * sites reporting nine uncheckable packages exactly as before.
         *
         * A vcs repository's versions are its tags, and GitHub will list those
         * over HTTP without a clone. That keeps the promise this command makes:
         * one cheap call, no Composer, safe on a schedule.
         */
        foreach ($this->githubRepositories() as $repo => $url) {
            if ($this->packageOf($url) !== $package) {
                continue;
            }

            foreach ($this->tags($repo) ?? [] as $tag) {
                $found[$tag] = true;

                $url = UpdateCheck::releaseUrl('https://github.com/'.$repo, $tag);

                if ($url !== null) {
                    $this->releaseNotes[$package][$tag] = $url;
                }
            }
        }

        return $found === [] ? null : array_keys($found);
    }

    /**
     * Release links for what versionsFor() found, version => URL. Only for
     * repositories on GitHub; from a Composer index, only where it names the
     * package's source.
     *
     * @return array<string,string>
     */
    public function releaseNotesFor(string $package): array
    {
        return $this->releaseNotes[$package] ?? [];
    }

    /**
     * @return array<string,string> owner/repo => the original url
     */
    protected function githubRepositories(): array
    {
        $out = [];

        foreach ($this->repositories->all() as $repo) {
            if (($repo['type'] ?? '') !== 'vcs') {
                continue;
            }

            $url = (string) ($repo['url'] ?? '');

            if (preg_match('#github\.com[:/]([^/]+)/([^/]+?)(?:\.git)?/?$#i', $url, $m)) {
                $out[$m[1].'/'.$m[2]] = $url;
            }
        }

        return $out;
    }

    /**
     * Which package a vcs repository provides.
     *
     * Asked of the repository's own composer.json, not guessed from the URL.
     * `github.com/ernestdefoe/fantasy-flarum` publishes `ernestdefoe/fantasy`,
     * and `github.com/ernestdefoe/gameday-flarum` publishes
     * `ernestdefoe/gameday` — matching on the URL would check the wrong package
     * or, worse, silently check nothing while appearing to work.
     */
    protected function packageOf(string $url): ?string
    {
        if (array_key_exists($url, $this->packageNames)) {
            return $this->packageNames[$url];
        }

        if (! preg_match('#github\.com[:/]([^/]+)/([^/]+?)(?:\.git)?/?$#i', $url, $m)) {
            return $this->packageNames[$url] = null;
        }

        // From GitHub's API, the one host a github.com token is sent to.
        $body = $this->get('https://api.github.com/repos/'.$m[1].'/'.$m[2].'/contents/composer.json', 'application/vnd.github.raw+json');
        $data = is_string($body) ? json_decode($body, true) : null;
        $name = is_array($data) && isset($data['name']) ? (string) $data['name'] : null;

        return $this->packageNames[$url] = ($name !== '' ? $name : null);
    }

    /** @return list<string>|null */
    protected function tags(string $repo): ?array
    {
        if (array_key_exists($repo, $this->tagLists)) {
            return $this->tagLists[$repo];
        }

        $body = $this->get('https://api.github.com/repos/'.$repo.'/tags?per_page=100');
        $data = is_string($body) ? json_decode($body, true) : null;

        if (! is_array($data)) {
            return $this->tagLists[$repo] = null;
        }

        $tags = [];

        foreach ($data as $tag) {
            if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
                $tags[] = $tag['name'];
            }
        }

        return $this->tagLists[$repo] = ($tags === [] ? null : $tags);
    }

    /** @return list<string> */
    protected function composerRepositories(): array
    {
        $urls = [];

        foreach ($this->repositories->all() as $repo) {
            /*
             * `composer` type only. A `vcs` repository has no index to read —
             * answering it would mean cloning, which is not something to do on a
             * nightly schedule — and a `path` repository is a checkout on this
             * machine, which the Extension Manager will not replace anyway.
             */
            if (($repo['type'] ?? '') !== 'composer') {
                continue;
            }

            $url = rtrim((string) ($repo['url'] ?? ''), '/');

            if ($url !== '' && str_starts_with($url, 'http')) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return array<string, list<string>>|null package => versions
     */
    protected function index(string $url): ?array
    {
        if (array_key_exists($url, $this->indexes)) {
            return $this->indexes[$url];
        }

        return $this->indexes[$url] = $this->fetchIndex($url);
    }

    /**
     * One HTTP GET, with the host's credential attached if there is one.
     *
     * Credentials are matched by HOST, the way Composer does it. A repository
     * at https://example.com/composer authenticates as example.com — looking one
     * up by URL finds nothing and the request 401s with no visible reason.
     *
     * `http_errors` off, so a 401 or 404 comes back as a body rather than as
     * an exception. A protected repository the site has no credential for is
     * a normal state — a lapsed subscription, a token not pasted in yet — and
     * the honest outcome is "could not check", never a failed scheduled command
     * that also abandons the packages it COULD have checked.
     */
    protected function get(string $url, string $accept = 'application/json'): ?string
    {
        $authorization = $this->auth->authorizationFor($url);

        try {
            return (string) $this->http->request('GET', $url, [
                'timeout' => $this->timeout,
                'http_errors' => false,
                // A redirect may not leave HTTPS, and Guzzle drops the credential when one leaves the host.
                'allow_redirects' => ['max' => 5, 'protocols' => ['https'], 'referer' => false],
                'headers' => ['User-Agent' => 'The Extension Manager', 'Accept' => $accept]
                    + ($authorization !== null ? ['Authorization' => $authorization] : []),
            ])->getBody();
        } catch (GuzzleException) {
            return null;
        }
    }

    /** @return array<string, list<string>>|null */
    protected function fetchIndex(string $url): ?array
    {
        $body = $this->get($url.'/packages.json');

        if (! is_string($body)) {
            return null;
        }

        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['packages']) || ! is_array($data['packages'])) {
            return null;
        }

        $out = [];

        foreach ($data['packages'] as $name => $versions) {
            if (! is_array($versions)) {
                continue;
            }

            $list = [];

            foreach ($versions as $key => $meta) {
                /*
                 * A Composer index keys each entry by version and usually
                 * repeats it inside. Prefer the inner value, because that is
                 * what Composer resolves against, and fall back to the key so a
                 * sparser index still works.
                 */
                $version = is_array($meta) && isset($meta['version'])
                    ? (string) $meta['version']
                    : (is_string($key) ? $key : null);

                if ($version !== null && $version !== '') {
                    $list[] = $version;

                    $url = UpdateCheck::releaseUrl((string) ($meta['source']['url'] ?? ''), $version);

                    if ($url !== null) {
                        $this->releaseNotes[(string) $name][$version] = $url;
                    }
                }
            }

            if ($list !== []) {
                $out[(string) $name] = array_values(array_unique($list));
            }
        }

        return $out;
    }
}
