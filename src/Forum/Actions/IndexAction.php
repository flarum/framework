<?php namespace Flarum\Forum\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;

class IndexAction extends ClientAction
{
    /**
     * A map of sort query param values to their API sort param.
     *
     * @var array
     */
    protected $sortMap = [
        'recent' => '-lastTime',
        'replies' => '-commentsCount',
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

        $params = [
            'sort' => $sort ? $this->sortMap[$sort] : '',
            'q' => $q
        ];

        // FIXME: make sure this is extensible. Support pagination.

        $document = $this->preload($params);

        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::index', compact('document')));

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
