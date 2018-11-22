<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
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
        $page = max(1, array_get($queryParams, 'page'));

        $params = [
            'id' => (int) array_get($queryParams, 'id'),
            'page' => [
                'near' => array_get($queryParams, 'near'),
                'offset' => ($page - 1) * 20,
                'limit' => 20
            ]
        ];

        $apiDocument = $this->getApiDocument($request->getAttribute('actor'), $params);

        $getResource = function ($link) use ($apiDocument) {
            return array_first($apiDocument->included, function ($value) use ($link) {
                return $value->type === $link->type && $value->id === $link->id;
            });
        };

        $url = function ($newQueryParams) use ($queryParams, $apiDocument) {
            $newQueryParams = array_merge($queryParams, $newQueryParams);
            unset($newQueryParams['id']);
            $queryString = http_build_query($newQueryParams);

            $idWithSlug = $apiDocument->data->id.(trim($apiDocument->data->attributes->slug) ? '-'.$apiDocument->data->attributes->slug : '');

            return $this->url->to('forum')->route('discussion', ['id' => $idWithSlug]).
            ($queryString ? '?'.$queryString : '');
        };

        $posts = [];

        foreach ($apiDocument->included as $resource) {
            if ($resource->type === 'posts' && isset($resource->relationships->discussion) && isset($resource->attributes->contentHtml)) {
                $posts[] = $resource;
            }
        }

        $document->title = $apiDocument->data->attributes->title;
        $document->canonicalUrl = $url([]);
        $document->content = $this->view->make('flarum.forum::frontend.content.discussion', compact('apiDocument', 'page', 'getResource', 'posts', 'url'));
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @param User $actor
     * @param array $params
     * @return object
     * @throws RouteNotFoundException
     */
    protected function getApiDocument(User $actor, array $params)
    {
        $response = $this->api->send('Flarum\Api\Controller\ShowDiscussionController', $actor, $params);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new RouteNotFoundException;
        }

        return json_decode($response->getBody());
    }
}
