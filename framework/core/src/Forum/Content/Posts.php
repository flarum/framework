<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Api\Client;
use Flarum\Api\Resource\PostResource;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Post search results.
 */
class Posts
{
    public function __construct(
        protected Client $api,
        protected Factory $view,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected PostResource $resource,
    ) {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $queryParams = $request->getQueryParams();

        $sort = Arr::pull($queryParams, 'sort');
        $q = Arr::pull($queryParams, 'q');
        $page = max(1, intval(Arr::pull($queryParams, 'page')));

        $sortMap = $this->resource->sortMap();

        $params = [
            ...$queryParams,
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '-createdAt',
            'page' => [
                'number' => $page
            ],
        ];

        if ($q) {
            $params['filter']['q'] = $q;
        }

        $apiDocument = $this->getApiDocument($request, $params);

        $document->title = $this->translator->trans('core.forum.index.meta_title_text');
//        $document->content = $this->view->make('flarum.forum::frontend.content.index', compact('apiDocument', 'page'));
        $document->payload['apiDocument'] = $apiDocument ?? ((object) ['data' => []]);

        $document->canonicalUrl = $this->url->to('forum')->route('posts', $params);
        $document->page = $page;
        $document->hasNextPage = $apiDocument && isset($apiDocument->links->next);

        return $document;
    }

    protected function getApiDocument(Request $request, array $params): ?object
    {
        return json_decode(
            json: $this->api
                ->withoutErrorHandling()
                ->withParentRequest($request)
                ->withQueryParams($params)
                ->get('/posts')
                ->getBody(),
            associative: false
        );
    }
}
