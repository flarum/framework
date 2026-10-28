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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Discussions a browser suggests as someone types in its search box, in the
 * OpenSearch suggestions format: the query, then titles, descriptions and
 * addresses. Only discussions the visitor can see are suggested.
 *
 * Titles are matched on the start of their words, so a word is completed as
 * it is typed. The forum's search matches whole words on MySQL, MariaDB and
 * PostgreSQL, so it can't suggest anything until a word is finished.
 */
class ShowOpenSearchSuggestionsController implements RequestHandlerInterface
{
    public const MIN_LENGTH = 2;

    public const LIMIT = 8;

    public function __construct(
        protected SlugManager $slugs,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = trim((string) Arr::get($request->getQueryParams(), 'q', ''));
        $words = preg_split('/[^\p{L}\p{N}\p{M}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $titles = $descriptions = $urls = [];

        if (mb_strlen(implode('', $words)) >= self::MIN_LENGTH) {
            $discussions = Discussion::whereVisibleTo(RequestUtil::getActor($request))
                ->where(function (Builder $titles) use ($words) {
                    foreach ($words as $word) {
                        $titles->where(fn (Builder $title) => $title
                            ->whereLike('discussions.title', "$word%")
                            ->orWhereLike('discussions.title', "% $word%"));
                    }
                })
                ->orderByDesc('discussions.last_posted_at')
                ->limit(self::LIMIT)
                ->get();

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
}
