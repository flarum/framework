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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $filters = Arr::pull($queryParams, 'filter', []);

        $sortMap = resolve('flarum.forum.discussions.sortmap');

        $params = [
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '',
            'filter' => $filters,
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        if ($q) {
            $params['filter']['q'] = $q;
        }

        $apiDocument = $this->getApiDocument($request, $params);
        $defaultRoute = $this->settings->get('default_route');

        $document->title = $this->translator->trans('core.forum.index.meta_title_text');
        $document->content = $this->view->make('flarum.forum::frontend.content.index', compact('apiDocument', 'page'));
        $document->payload['apiDocument'] = $apiDocument;

        $document->canonicalUrl = $this->url->to('forum')->base().($defaultRoute === '/all' ? '' : $request->getUri()->getPath());
        $document->page = $page;
        $document->hasNextPage = isset($apiDocument->links->next);

        return $document;
    }

    /**
     * Get the result of an API request to list discussions.
     *
     * @param Request $request
     * @param array $params
     * @return object
     */
    protected function getApiDocument(Request $request, array $params)
    {
        return json_decode($this->api->withParentRequest($request)->withQueryParams($params)->get('/discussions')->getBody());
    }
}
