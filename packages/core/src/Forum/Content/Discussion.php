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
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Client $api
     * @param UrlGenerator $url
     * @param Factory $view
     */
    public function __construct(Client $api, UrlGenerator $url, Factory $view)
    {
        $this->api = $api;
        $this->url = $url;
        $this->view = $view;
    }

    public function __invoke(Document $document, Request $request)
    {
        $queryParams = $request->getQueryParams();
        $id = Arr::get($queryParams, 'id');
        $near = intval(Arr::get($queryParams, 'near'));
        $page = max(1, intval(Arr::get($queryParams, 'page')), 1 + intdiv($near, 20));

        $params = [
            'id' => $id,
            'page' => [
                'near' => $near,
                'offset' => ($page - 1) * 20,
                'limit' => 20
            ]
        ];

        $apiDocument = $this->getApiDocument($request, $id, $params);

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

        $posts = [];

        foreach ($apiDocument->included as $resource) {
            if ($resource->type === 'posts' && isset($resource->relationships->discussion) && isset($resource->attributes->contentHtml)) {
                $posts[] = $resource;
            }
        }

        $hasPrevPage = $page > 1;
        $hasNextPage = $page < 1 + intval($apiDocument->data->attributes->commentCount / 20);

        $document->title = $apiDocument->data->attributes->title;
        $document->canonicalUrl = $url(['page' => $page]);
        $document->content = $this->view->make('flarum.forum::frontend.content.discussion', compact('apiDocument', 'page', 'hasPrevPage', 'hasNextPage', 'getResource', 'posts', 'url'));
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @throws RouteNotFoundException
     */
    protected function getApiDocument(Request $request, string $id, array $params)
    {
        $params['bySlug'] = true;
        $response = $this->api
            ->withParentRequest($request)
            ->withQueryParams($params)
            ->get("/discussions/$id");
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new RouteNotFoundException;
        }

        return json_decode($response->getBody());
    }
}
