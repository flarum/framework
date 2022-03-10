<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Locale\LocaleManager;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorePayload
{
    /**
     * @var LocaleManager
     */
    private $locales;

    /**
     * @var Client
     */
    private $api;

    /**
     * @param LocaleManager $locales
     * @param Client $api
     */
    public function __construct(LocaleManager $locales, Client $api)
    {
        $this->locales = $locales;
        $this->api = $api;
    }

    public function __invoke(Document $document, Request $request)
    {
        $document->payload = array_merge(
            $document->payload,
            $this->buildPayload($document, $request)
        );
    }

    private function buildPayload(Document $document, Request $request)
    {
        $data = $this->getDataFromApiDocument($document->getForumApiDocument());

        $actor = RequestUtil::getActor($request);

        if ($actor->exists) {
            $user = $this->getUserApiDocument($request, $actor);
            $data = array_merge($data, $this->getDataFromApiDocument($user));
        }

        return [
            'resources' => $data,
            'session' => [
                'userId' => $actor->id,
                'csrfToken' => $request->getAttribute('session')->token()
            ],
            'locales' => $this->locales->getLocales(),
            'locale' => $request->getAttribute('locale')
        ];
    }

    private function getDataFromApiDocument(array $apiDocument): array
    {
        $data[] = $apiDocument['data'];

        if (isset($apiDocument['included'])) {
            $data = array_merge($data, $apiDocument['included']);
        }

        return $data;
    }

    private function getUserApiDocument(Request $request, User $actor): array
    {
        $id = $actor->id;

        return $this->getResponseBody(
            $this->api->withParentRequest($request)->get("/users/$id")
        );
    }

    private function getResponseBody(ResponseInterface $response)
    {
        return json_decode($response->getBody(), true);
    }
}
