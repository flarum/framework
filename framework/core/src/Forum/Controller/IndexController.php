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

use Flarum\Core\User;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends ClientController
{
    /**
     * A map of sort query param values to their API sort param.
     *
     * @var array
     */
    private $sortMap = [
        'latest' => '-lastTime',
        'top' => '-commentsCount',
        'newest' => '-startTime',
        'oldest' => 'startTime'
    ];

    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        $view = parent::render($request);

        $queryParams = $request->getQueryParams();

        $sort = array_pull($queryParams, 'sort');
        $q = array_pull($queryParams, 'q');
        $page = array_pull($queryParams, 'page', 1);

        $params = [
            'sort' => $sort && isset($this->sortMap[$sort]) ? $this->sortMap[$sort] : '',
            'filter' => compact('q'),
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        $document = $this->preload($request->getAttribute('actor'), $params);

        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::index', compact('document', 'page', 'forum')));

        return $view;
    }

    /**
     * Get the result of an API request to list discussions.
     *
     * @param User $actor
     * @param array $params
     * @return object
     */
    protected function preload(User $actor, array $params)
    {
        return json_decode($this->api->send('Flarum\Api\Controller\ListDiscussionsController', $actor, $params)->getBody());
    }
}
