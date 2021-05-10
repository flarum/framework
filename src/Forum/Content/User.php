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
use Flarum\Http\UrlGenerator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class User
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
     * @param Client $api
     * @param UrlGenerator $url
     */
    public function __construct(Client $api, UrlGenerator $url)
    {
        $this->api = $api;
        $this->url = $url;
    }

    public function __invoke(Document $document, Request $request)
    {
        $queryParams = $request->getQueryParams();
        $username = Arr::get($queryParams, 'username');

        $apiDocument = $this->getApiDocument($request, $username);
        $user = $apiDocument->data->attributes;

        $document->title = $user->displayName;
        $document->canonicalUrl = $this->url->to('forum')->route('user', ['username' => $user->slug]);
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get the result of an API request to show a user.
     *
     * @throws ModelNotFoundException
     */
    protected function getApiDocument(Request $request, string $username)
    {
        $response = $this->api->withParentRequest($request)->withQueryParams(['bySlug' => true])->get("/users/$username");
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new ModelNotFoundException;
        }

        return json_decode($response->getBody());
    }
}
