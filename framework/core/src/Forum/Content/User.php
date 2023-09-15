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
use Illuminate\Http\Request;

class User
{
    public function __construct(
        protected Client $api,
        protected UrlGenerator $url
    ) {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $username = $request->route('username');

        $apiDocument = $this->getApiDocument($request, $username);
        $user = $apiDocument->data->attributes;

        $document->title = $user->displayName;
        $document->canonicalUrl = $this->url->route('forum.user', ['username' => $user->slug]);
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get the result of an API request to show a user.
     *
     * @throws ModelNotFoundException
     */
    protected function getApiDocument(Request $request, string $username): object
    {
        $response = $this->api->withParentRequest($request)->withQueryParams(['bySlug' => true])->get("/users/$username");
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new ModelNotFoundException;
        }

        return json_decode($response->content());
    }
}
