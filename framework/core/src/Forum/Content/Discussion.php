<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Discussion
{
    public function __construct(
        protected Client $api,
        protected UrlGenerator $url,
        protected Factory $view
    ) {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $queryParams = $request->getQueryParams();
        $id = Arr::get($queryParams, 'id');
        $near = intval(Arr::get($queryParams, 'near'));
        $page = max(1, intval(Arr::get($queryParams, 'page')), 1 + intdiv($near, 20));

        $apiDocument = $this->getApiDocument($request, $id);

        $getResource = function ($link) use ($apiDocument) {
            return Arr::first($apiDocument->included, function ($value) use ($link) {
                return $value->type === $link->type && $value->id === $link->id;
            });
        };

        $url = function ($newQueryParams) use ($queryParams, $apiDocument) {
            $newQueryParams = array_merge($queryParams, $newQueryParams);
            unset($newQueryParams['id']);
            unset($newQueryParams['near']);

            if (Arr::get($newQueryParams, 'page') == 1) {
                unset($newQueryParams['page']);
            }

            $queryString = http_build_query($newQueryParams);

            return $this->url->to('forum')->route('discussion', ['id' => $apiDocument->data->attributes->slug]).
                ($queryString ? '?'.$queryString : '');
        };

        $params = [
            'filter' => [
                'discussion' => intval($id),
            ],
            'page' => [
                'near' => $near,
                'offset' => ($page - 1) * 20,
                'limit' => 20,
            ],
        ];

        $postsApiDocument = $this->getPostsApiDocument($request, $params);
        $posts = [];

        foreach ($postsApiDocument->data as $resource) {
            if ($resource->type === 'posts' && isset($resource->relationships->discussion) && isset($resource->attributes->contentHtml)) {
                $posts[] = $resource;
            }
        }

        $hasPrevPage = $page > 1;
        $hasNextPage = $page < 1 + intval($apiDocument->data->attributes->commentCount / 20);

        $document->title = $apiDocument->data->attributes->title;
        $document->content = $this->view->make('flarum.forum::frontend.content.discussion', compact('apiDocument', 'page', 'hasPrevPage', 'hasNextPage', 'getResource', 'posts', 'url'));

        $apiDocument->included = array_values(array_filter($apiDocument->included, function ($value) {
            return $value->type !== 'posts';
        }));
        $apiDocument->included = array_merge($apiDocument->included, $postsApiDocument->data, $postsApiDocument->included);
        $apiDocument->included = array_values(array_filter($apiDocument->included, function ($value) use ($apiDocument) {
            return $value->type !== 'discussions' || $value->id !== $apiDocument->data->id;
        }));

        $document->payload['apiDocument'] = $apiDocument;

        $document->canonicalUrl = $url([]);
        $document->page = $page;
        $document->hasNextPage = $hasNextPage;

        return $document;
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @throws RouteNotFoundException
     */
    protected function getApiDocument(Request $request, string $id, array $params = []): object
    {
        $params['bySlug'] = true;

        return json_decode(
            $this->api
                ->withoutErrorHandling()
                ->withParentRequest($request)
                ->withQueryParams($params)
                ->get("/discussions/$id")
                ->getBody()
        );
    }

    /**
     * Get the result of an API request to list the posts of a discussion.
     *
     * @throws RouteNotFoundException
     */
    protected function getPostsApiDocument(Request $request, array $params): object
    {
        return json_decode(
            $this->api
                ->withoutErrorHandling()
                ->withParentRequest($request)
                ->withQueryParams($params)
                ->get('/posts')
                ->getBody()
        );
    }
}
