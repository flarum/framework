<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Discover;

use Flarum\ExtensionManager\Lang;
/**
 * Finding extensions, in two cheap requests rather than one slow one.
 *
 * 🚨 The split is the design. Packagist's search tells you a package exists and
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
     * What each category adds to the search, the way the previous Extension Manager splits its
     * list. Packagist has no category field, so each is the nearest thing it
     * does have, chosen by measuring what comes back (2026-10-13):
     *
     * - themes by the word, not the tag: `tags=theme` finds 24 and misses Asirem
     *   and Fluent; the word finds 78, and every one of those 24.
     * - languages and widgets by tag, which Packagist ORs: 319 and 37, against
     *   137 for the previous Extension Manager's `flarum-lang` (one vendor's packs only).
     *
     * @var array<string,array{q?:string,tags?:list<string>}>
     */
    public const CATEGORIES = [
        'theme' => ['q' => 'theme'],
        'language' => ['tags' => ['locale', 'language']],
        'widget' => ['tags' => ['widget', 'widgets']],
    ];

    /** @param ?callable(string):?string $get overridable so tests never touch a network */
    public function __construct(
        private Cache $cache,
        private $get = null,
    ) {
        $this->get ??= fn (string $url) => $this->fetch($url);
    }

    /**
     * Search, or — with no query — browse.
     *
     * 🚨 An empty query is a valid request, not an error. Packagist answers
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

        $body = ($this->get)($url);

        if ($body === null) {
            /*
             * 🚨 Reported, never turned into an empty list. "Nothing found" and
             * "could not reach Packagist" look identical on screen and mean
             * opposite things — the first ends a search, the second means try
             * again in a minute.
             */
            return ['results' => [], 'total' => 0, 'error' => Lang::trans('discover.unreachable'), 'more' => false];
        }

        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['results'])) {
            return ['results' => [], 'total' => 0, 'error' => Lang::trans('discover.unexpected'), 'more' => false];
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
                 * 🚨 Carried through rather than dropped. Packagist marks a
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
         * 🚨 The core version is part of the key. A verdict is not a fact about
         * a package, it is a fact about a package AND the Flarum asking — so a
         * cache keyed on the name alone would keep answering for the version the
         * site used to run for a day after an upgrade, which is exactly when
         * somebody goes looking for extensions that now work.
         */
        // "v2": verdicts cached before replacements were detected carry no
        // replaced flag, and must not be read as "not replaced".
        $scope = 'compat:v2:'.$compat->coreVersion().':';

        foreach ($names as $name) {
            $out[$name] = $this->one($name, $compat, $scope);
        }

        /*
         * 🚨 Followed to the end. jaspervriends/flarum-seo names
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
    private function one(string $name, Compatibility $compat, string $scope): array
    {
        $cached = $this->cache->get($scope.$name);

        if ($cached !== null) {
            return $cached;
        }

        $body = ($this->get)('https://repo.packagist.org/p2/'.$name.'.json');

        if ($body === null) {
            // Not cached: a package that could not be reached today may be
            // reachable in a minute, and caching "unknown" would hide it.
            return ['compatible' => null, 'version' => null, 'requires' => null, 'stability' => null, 'replaced' => false, 'replacedBy' => null];
        }

        $verdict = $compat->verdict($name, (array) json_decode($body, true));

        if ($verdict['replaced'] && $verdict['replacedBy'] === null) {
            $verdict['replacedBy'] = $this->realName($name, (array) $verdict['source']);
        }

        unset($verdict['source']);
        $this->cache->put($scope.$name, $verdict);

        return $verdict;
    }

    /**
     * The name a moved repository goes by now, read from its own composer.json.
     *
     * Only ever asked for a package whose newest release replaces its own name,
     * so this is one extra request for a rare case, cached with the verdict.
     * 🚨 GitHub only, and the host is fixed here rather than taken from the
     * metadata: a source URL is somebody else's data, and following it wherever
     * it points would let a package steer this server's requests. Anywhere else,
     * the answer is "replaced, by something we can't name" — install is still
     * refused, just without a suggestion.
     *
     * @param array<string,mixed> $source
     */
    private function realName(string $name, array $source): ?string
    {
        $url = (string) ($source['url'] ?? '');
        $ref = (string) ($source['reference'] ?? '');

        if (! preg_match('#^https://github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#', $url, $m)
            || ! preg_match('#^[A-Za-z0-9._/-]{1,100}$#', $ref) || str_contains($ref, '..')) {
            return null;
        }

        $body = ($this->get)("https://raw.githubusercontent.com/{$m[1]}/{$m[2]}/{$ref}/composer.json");
        $real = strtolower((string) (json_decode((string) $body, true)['name'] ?? ''));

        return preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $real) && $real !== strtolower($name)
            ? $real
            : null;
    }

    private function fetch(string $url): ?string
    {
        $context = stream_context_create(['http' => [
            'timeout' => 15,
            'header' => "User-Agent: the Extension Manager (Flarum extension updater)\r\n",
        ]]);

        $body = @file_get_contents($url, false, $context);

        return $body === false ? null : $body;
    }
}
