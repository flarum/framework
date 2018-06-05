<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Forum\ForumFrontend;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends FrontendController
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
     * @param ForumFrontend $frontend
     * @param Dispatcher $events
     * @param Client $api
     * @param Factory $view
     */
    public function __construct(ForumFrontend $frontend, Dispatcher $events, Client $api, Factory $view)
    {
        parent::__construct($frontend, $events);

        $this->api = $api;
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function getView(Request $request)
    {
        $view = parent::getView($request);

        $queryParams = $request->getQueryParams();

        $sort = array_pull($queryParams, 'sort');
        $q = array_pull($queryParams, 'q');
        $page = array_pull($queryParams, 'page', 1);

        $sortMap = $this->getSortMap();

        $params = [
            'sort' => $sort && isset($sortMap[$sort]) ? $sortMap[$sort] : '',
            'filter' => compact('q'),
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        $document = $this->getDocument($request->getAttribute('actor'), $params);

        $view->document = $document;
        $view->content = $this->view->make('flarum.forum::frontend.content.index', compact('document', 'page', 'forum'));

        return $view;
    }

    /**
     * Get a map of sort query param values and their API sort params.
     *
     * @return array
     */
    private function getSortMap()
    {
        return [
            'latest' => '-lastTime',
            'top' => '-commentsCount',
            'newest' => '-startTime',
            'oldest' => 'startTime'
        ];
    }

    /**
     * Get the result of an API request to list discussions.
     *
     * @param User $actor
     * @param array $params
     * @return object
     */
    private function getDocument(User $actor, array $params)
    {
        return json_decode($this->api->send(ListDiscussionsController::class, $actor, $params)->getBody());
    }
}
