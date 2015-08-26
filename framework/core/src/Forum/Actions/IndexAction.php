<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;

class IndexAction extends ClientAction
{
    /**
     * A map of sort query param values to their API sort param.
     *
     * @var array
     */
    protected $sortMap = [
        'latest' => '-lastTime',
        'top' => '-commentsCount',
        'newest' => '-startTime',
        'oldest' => '+startTime'
    ];

    /**
     * {@inheritdoc}
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $queryParams = $request->getQueryParams();

        $sort = array_pull($queryParams, 'sort');
        $q = array_pull($queryParams, 'q');
        $page = array_pull($queryParams, 'page', 1);

        $params = [
            'sort' => $sort && isset($this->sortMap[$sort]) ? $this->sortMap[$sort] : '',
            'filter' => compact('q'),
            'page' => ['offset' => ($page - 1) * 20, 'limit' => 20]
        ];

        $document = $this->preload($params);

        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::index', compact('document', 'page', 'forum')));

        return $view;
    }

    /**
     * Get the result of an API request to list discussions.
     *
     * @param array $params
     * @return object
     */
    protected function preload(array $params)
    {
        $actor = app('flarum.actor');
        $action = 'Flarum\Api\Actions\Discussions\IndexAction';

        return $this->apiClient->send($actor, $action, $params)->getBody();
    }
}
