<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Api\Client;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Translation\TranslatorInterface;

class Index
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Client $api
     * @param Factory $view
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(Client $api, Factory $view, SettingsRepositoryInterface $settings, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->api = $api;
        $this->view = $view;
        $this->settings = $settings;
        $this->url = $url;
        $this->translator = $translator;
    }

    public function __invoke(Document $document, Request $request)
    {
        $queryParams = $request->getQueryParams();

        $sort = Arr::pull($queryParams, 'sort');
        $q = Arr::pull($queryParams, 'q');
        $page = max(1, intval(Arr::pull($queryParams, 'page')));

        $sortMap = $this->getSortMap();

        $params = [
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '',
            'filter' => compact('q'),
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        $apiDocument = $this->getApiDocument($request->getAttribute('actor'), $params);
        $defaultRoute = $this->settings->get('default_route');

        $document->title = $this->translator->trans('core.forum.index.meta_title_text');
        $document->content = $this->view->make('flarum.forum::frontend.content.index', compact('apiDocument', 'page'));
        $document->payload['apiDocument'] = $apiDocument;
        $document->canonicalUrl = $defaultRoute === '/all' ? $this->url->to('forum')->base() : $request->getUri()->withQuery('');

        return $document;
    }

    /**
     * Get a map of sort query param values and their API sort params.
     *
     * @return array
     */
    private function getSortMap()
    {
        return [
            'latest' => '-lastPostedAt',
            'top' => '-commentCount',
            'newest' => '-createdAt',
            'oldest' => 'createdAt'
        ];
    }

    /**
     * Get the result of an API request to list discussions.
     *
     * @param User $actor
     * @param array $params
     * @return object
     */
    private function getApiDocument(User $actor, array $params)
    {
        return json_decode($this->api->send(ListDiscussionsController::class, $actor, $params)->getBody());
    }
}
