<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Normalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Discussions a browser suggests as someone types in its search box, in the
 * OpenSearch suggestions format: the query, then titles, descriptions and
 * addresses. Only discussions the visitor can see are suggested.
 *
 * Titles are matched on the start of their words, so a word is completed as
 * it is typed. A word starts after anything that isn't a letter or digit, as
 * in "[Solved]" or "carbon-fibre", which LIKE can't express: the database
 * finds titles that contain the words, and the starts are checked here. The forum's search matches whole words on MySQL, MariaDB and
 * PostgreSQL, so it can't suggest anything until a word is finished.
 */
class ShowOpenSearchSuggestionsController implements RequestHandlerInterface
{
    public const MIN_LENGTH = 2;

    public const LIMIT = 8;

    /**
     * Words of the query that are matched; the rest are ignored, so a long
     * query can't build an unbounded number of conditions.
     */
    public const MAX_WORDS = 8;

    /**
     * Recently active titles containing the words that are checked for words
     * starting with them. Older matches are missed when this many titles only
     * contain the words inside other words.
     */
    public const CANDIDATES = 100;

    public function __construct(
        protected SlugManager $slugs,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $q = Arr::get($request->getQueryParams(), 'q');
        $query = is_string($q) ? $q : '';
        $words = preg_split('/[^\p{L}\p{N}\p{M}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_slice($words, 0, self::MAX_WORDS);
        $titles = $descriptions = $urls = [];

        if (mb_strlen(implode('', $words)) >= self::MIN_LENGTH) {
            $discussions = Discussion::whereVisibleTo(RequestUtil::getActor($request))
                ->where(function (Builder $titles) use ($words) {
                    foreach ($words as $word) {
                        $titles->where(function (Builder $title) use ($word) {
                            foreach ($this->cases($word) as $case) {
                                $title->orWhereLike('discussions.title', "%$case%");
                            }
                        });
                    }
                })
                ->orderByDesc('discussions.last_posted_at')
                ->limit(self::CANDIDATES)
                ->get()
                ->filter(fn (Discussion $discussion) => $this->startsWords($discussion->title, $words))
                ->take(self::LIMIT);

            foreach ($discussions as $discussion) {
                $titles[] = $discussion->title;
                $descriptions[] = '';
                $urls[] = $this->url->to('forum')->route('discussion', ['id' => $this->slugs->forResource(Discussion::class)->toSlug($discussion)]);
            }
        }

        return new JsonResponse([$query, $titles, $descriptions, $urls], 200, [
            'Content-Type' => 'application/x-suggestions+json',
            'Cache-Control' => 'private, max-age=60',
        ], JsonResponse::DEFAULT_JSON_FLAGS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Whether every word starts a word of the title, ignoring case and accents
     * as MySQL, MariaDB and PostgreSQL do when finding the titles.
     *
     * @param string[] $words
     */
    protected function startsWords(string $title, array $words): bool
    {
        $title = $this->withoutAccents($title);

        foreach ($words as $word) {
            if (! preg_match('/(?<![\p{L}\p{N}\p{M}])'.preg_quote($this->withoutAccents($word), '/').'/iu', $title)) {
                return false;
            }
        }

        return true;
    }

    protected function withoutAccents(string $text): string
    {
        return (string) preg_replace('/\p{Mn}+/u', '', (string) Normalizer::normalize($text, Normalizer::FORM_D));
    }

    /**
     * The word as typed and, when it has letters beyond ASCII, as it would be
     * written in lower, upper and title case: SQLite's LIKE ignores the case
     * of ASCII letters only.
     *
     * @return string[]
     */
    protected function cases(string $word): array
    {
        if (! preg_match('/[^\x00-\x7F]/', $word)) {
            return [$word];
        }

        return array_values(array_unique([
            $word,
            mb_strtolower($word),
            mb_strtoupper($word),
            mb_convert_case($word, MB_CASE_TITLE),
        ]));
    }
}
