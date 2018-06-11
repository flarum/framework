<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Api\Client;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Frontend\FrontendView;
use Flarum\Locale\LocaleManager;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorePayload implements ContentInterface
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

    public function populate(FrontendView $view, Request $request)
    {
        $view->payload = array_merge(
            $view->payload,
            $this->buildPayload($view, $request)
        );
    }

    private function buildPayload(FrontendView $view, Request $request)
    {
        $data = $this->getDataFromDocument($view->getForumDocument());

        $actor = $request->getAttribute('actor');

        if ($actor->exists) {
            $user = $this->getUserDocument($actor);
            $data = array_merge($data, $this->getDataFromDocument($user));
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

    private function getDataFromDocument(array $document): array
    {
        $data[] = $document['data'];

        if (isset($document['included'])) {
            $data = array_merge($data, $document['included']);
        }

        return $data;
    }

    private function getUserDocument(User $user): array
    {
        // TODO: to avoid an extra query, something like
        // $controller = new ShowUserController(new PreloadedUserRepository($user));

        return $this->getResponseBody(
            $this->api->send(ShowUserController::class, $user, ['id' => $user->id])
        );
    }

    private function getResponseBody(ResponseInterface $response)
    {
        return json_decode($response->getBody(), true);
    }
}
