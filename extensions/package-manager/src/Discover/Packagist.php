<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Discover;

use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Finding extensions, in two cheap requests rather than one slow one.
 *
 * The split is the design. Packagist's search tells you a package exists and
 * nothing about whether it works with your Flarum; that answer lives in each
 * package's own metadata file, one HTTP call per package. Doing all of it in the
 * request that serves a search means a dozen round trips before anything appears
 * — on a host that may cut the request at thirty seconds, for a screen somebody
 * is typing into.
 *
 * So search returns immediately with what search knows, and the verdicts are
 * asked for separately and cached. The screen fills in.
 */
class Packagist
{
    /**
     * What each category adds to the search. Packagist has no category field, so each is the nearest thing it
     * does have, chosen by measuring what comes back (2026-10-13):
     *
     * - themes by the word, not the tag: `tags=theme` finds 24 and misses Asirem
     *   and Fluent; the word finds 78, and every one of those 24.
     * - languages and widgets by tag, which Packagist ORs: 319 and 37, against
     *   137 for the `flarum-lang` tag alone (one vendor's packs only).
     *
     * @var array<string,array{q?:string,tags?:list<string>}>
     */
    public const CATEGORIES = [
        'theme' => ['q' => 'theme'],
        'language' => ['tags' => ['locale', 'language']],
        'widget' => ['tags' => ['widget', 'widgets']],
    ];

    public function __construct(
        protected TranslatorInterface $translator,
        protected Cache $cache,
        protected ClientInterface $http,
    ) {
    }

    /**
     * Search, or — with no query — browse.
     *
     * An empty query is a valid request, not an error. Packagist answers
     * `type=flarum-extension` with no `q` by returning every Flarum extension
     * published, most popular first: 2306 of them. That is what somebody opening
     * a tab called "Find extensions" is asking for, and requiring them to guess
     * a search term before anything appears makes a browsable catalogue behave
     * like a command line.
     *
     * @return array{results:list<array<string,mixed>>, total:int, error:?string, more:bool}
     */
    public function search(string $query, int $perPage = 12, int $page = 1, string $category = ''): array
    {
        // An unknown category is no category, so the parameter cannot reach the URL as typed.
        $narrow = self::CATEGORIES[$category] ?? [];
        $query = trim($query.' '.($narrow['q'] ?? ''));

        $url = 'https://packagist.org/search.json?type=flarum-extension&per_page='.$perPage
            .'&page='.max(1, $page)
            .($query === '' ? '' : '&q='.rawurlencode($query));

        foreach ($narrow['tags'] ?? [] as $tag) {
            $url .= '&tags[]='.rawurlencode($tag);
        }

        $body = $this->fetch($url);

        if ($body === null) {
            /*
             * Reported, never turned into an empty list. "Nothing found" and
             * "could not reach Packagist" look identical on screen and mean
             * opposite things — the first ends a search, the second means try
             * again in a minute.
             */
            return ['results' => [], 'total' => 0, 'error' => $this->translator->trans('flarum-extension-manager.api.discover.unreachable'), 'more' => false];
        }

        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['results'])) {
            return ['results' => [], 'total' => 0, 'error' => $this->translator->trans('flarum-extension-manager.api.discover.unexpected'), 'more' => false];
        }

        $results = [];

        foreach ((array) $data['results'] as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $results[] = [
                'name' => $name,
                'description' => (string) ($row['description'] ?? ''),
                'downloads' => (int) ($row['downloads'] ?? 0),
                'favers' => (int) ($row['favers'] ?? 0),
                'repository' => (string) ($row['repository'] ?? ''),
                /*
                 * Carried through rather than dropped. Packagist marks a
                 * package abandoned when its author says so, and installing one
                 * unknowingly is exactly the kind of thing somebody would want
                 * to have been told before rather than after.
                 */
                'abandoned' => $row['abandoned'] ?? false,
            ];
        }

        return [
            'results' => $results,
            'total' => (int) ($data['total'] ?? count($results)),
            'error' => null,
            // Packagist hands back the URL of the next page when there is one.
            'more' => ! empty($data['next']),
        ];
    }

    /**
     * Compatibility verdicts for a handful of packages, from cache where possible.
     *
     * @param list<string> $names
     * @return array<string,array<string,mixed>>
     */
    public function verdicts(array $names, Compatibility $compat): array
    {
        $out = [];

        /*
         * The core version is part of the key. A verdict is not a fact about
         * a package, it is a fact about a package AND the Flarum asking — so a
         * cache keyed on the name alone would keep answering for the version the
         * site used to run for a day after an upgrade, which is exactly when
         * somebody goes looking for extensions that now work.
         */
        // "v4": verdicts cached before then call `2.0.0-b2` stable, call an
        // extension that declares no Flarum version incompatible, and may be
        // an unreadable answer kept as "unknown", for up to a day.
        $scope = 'compat:v4:'.$compat->coreVersion().':';

        foreach ($names as $name) {
            $out[$name] = $this->one($name, $compat, $scope);
        }

        /*
         * Followed to the end. jaspervriends/flarum-seo names
         * v17development/flarum-seo as its replacement, which is itself an old
         * name for fof/seo: offering the middle one would install the very
         * thing this guards against. Three hops at most, and never in a circle.
         */
        foreach ($out as $name => $verdict) {
            $seen = [$name => true];

            for ($hop = 0; $hop < 3 && ! empty($verdict['replacedBy']) && ! isset($seen[$verdict['replacedBy']]); $hop++) {
                $next = $this->one($verdict['replacedBy'], $compat, $scope);
                $seen[$verdict['replacedBy']] = true;

                if (empty($next['replaced']) || empty($next['replacedBy'])) {
                    break;
                }

                $out[$name]['replacedBy'] = $verdict['replacedBy'] = $next['replacedBy'];
            }
        }

        return $out;
    }

    /** @return array<string,mixed> */
    protected function one(string $name, Compatibility $compat, string $scope): array
    {
        $cached = $this->cache->get($scope.$name);

        if ($cached !== null) {
            return $cached;
        }

        $p2 = $this->p2($name, '', $status);

        /*
         * Packagist keeps branches in a second file. A package that has never
         * been tagged (fof/translate, fof/extension-releases) has an empty list
         * in the first, and read alone that is "could not check" for something
         * a forum on Dev stability installs fine. One more request, and only for
         * packages with no releases at all.
         */
        if ($p2 !== null && $p2['packages'][$name] === []) {
            $dev = $this->p2($name, '~dev', $status);
            $p2 = $dev ?? ($status === 404 ? $p2 : null);
        }

        if ($p2 === null) {
            /*
             * Not cached: a package that could not be reached today may be
             * reachable in a minute, and caching "unknown" would hide it. That
             * includes an answer that does not parse, which used to be cached
             * as "unknown" for a day. Anything but a 404 is Packagist not
             * answering, which the install guard needs to tell from Packagist
             * not knowing the package.
             */
            return ['compatible' => null, 'version' => null, 'requires' => null, 'stability' => null, 'replaced' => false, 'replacedBy' => null,
                'unreachable' => $status !== 404 && $status !== 410];
        }

        $verdict = $compat->verdict($name, $p2);

        if ($verdict['replaced'] && $verdict['replacedBy'] === null) {
            $verdict['replacedBy'] = $this->realName($name, (array) $verdict['source']);
        }

        unset($verdict['source']);
        $this->cache->put($scope.$name, $verdict);

        return $verdict;
    }

    /**
     * A package's p2 file, decoded, or null when there is no readable one.
     *
     * @param int|null $status set to the response's status, 0 for none
     * @return array<string,mixed>|null
     */
    protected function p2(string $name, string $suffix, ?int &$status): ?array
    {
        $data = json_decode((string) $this->fetch('https://repo.packagist.org/p2/'.$name.$suffix.'.json', $status), true);

        return is_array($data) && is_array($data['packages'][$name] ?? null) ? $data : null;
    }

    /**
     * Refuse to install a package under a name another package has taken
     * over: installing v17development/flarum-seo gave forums FoF SEO under the
     * wrong id, which then refused to enable (discuss.flarum.org d/40012).
     *
     * Every package is checked, not the first five. And when Packagist cannot
     * be asked, the install is refused too: the guard cannot vouch for the
     * name, and a resolve against an unreachable Packagist would fail anyway,
     * so "try again in a minute" costs nothing that was going to work. A
     * package Packagist does not know (a protected one) is not refused.
     *
     * @param list<string> $names
     * @throws RefusedException
     */
    public function refuseReplaced(array $names, Compatibility $compat): void
    {
        $verdicts = $this->verdicts($names, $compat);

        foreach ($verdicts as $name => $verdict) {
            if (! empty($verdict['replaced'])) {
                throw new RefusedException($verdict['replacedBy']
                    ? $this->translator->trans('flarum-extension-manager.api.start.replaced_by', ['name' => $name, 'replacement' => $verdict['replacedBy']])
                    : $this->translator->trans('flarum-extension-manager.api.start.replaced', ['name' => $name]), 'package_replaced');
            }
        }

        foreach ($verdicts as $name => $verdict) {
            if (! empty($verdict['unreachable'])) {
                throw new RefusedException($this->translator->trans('flarum-extension-manager.api.start.replaced_unchecked', ['name' => $name]), 'packagist_unreachable', 503);
            }
        }
    }

    /**
     * The name a moved repository goes by now, read from its own composer.json.
     *
     * Only ever asked for a package whose newest release replaces its own name,
     * so this is one extra request for a rare case, cached with the verdict.
     * GitHub only, and the host is fixed here rather than taken from the
     * metadata: a source URL is somebody else's data, and following it wherever
     * it points would let a package steer this server's requests. Anywhere else,
     * the answer is "replaced, by something we can't name" — install is still
     * refused, just without a suggestion.
     *
     * @param array<string,mixed> $source
     */
    protected function realName(string $name, array $source): ?string
    {
        $url = (string) ($source['url'] ?? '');
        $ref = (string) ($source['reference'] ?? '');

        if (! preg_match('#^https://github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#', $url, $m)
            || ! preg_match('#^[A-Za-z0-9._/-]{1,100}$#', $ref) || str_contains($ref, '..')) {
            return null;
        }

        $body = $this->fetch("https://raw.githubusercontent.com/{$m[1]}/{$m[2]}/{$ref}/composer.json");
        $real = strtolower((string) (json_decode((string) $body, true)['name'] ?? ''));

        return preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $real) && $real !== strtolower($name)
            ? $real
            : null;
    }

    /**
     * The body, or null when the request failed or was answered with an error.
     *
     * @param int|null $status set to the response's status, 0 for none
     */
    protected function fetch(string $url, ?int &$status = null): ?string
    {
        $status = 0;

        try {
            $response = $this->http->request('GET', $url, [
                'timeout' => 15,
                'http_errors' => false,
                'headers' => ['User-Agent' => 'the Extension Manager (Flarum extension updater)'],
            ]);
        } catch (GuzzleException) {
            return null;
        }

        $status = $response->getStatusCode();

        return $status < 300 ? (string) $response->getBody() : null;
    }
}
