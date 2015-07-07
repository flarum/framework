<?php namespace Flarum\Forum\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;

class DiscussionAction extends ClientAction
{
    /**
     * {@inheritdoc}
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $params = [
            'id' => array_get($routeParams, 'id'),
            'page.near' => array_get($routeParams, 'near')
        ];

        // FIXME: make sure this is extensible. 404s, pagination.

        $document = $this->preload($params);

        $view->setTitle($document->data->attributes->title);
        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::discussion', compact('document')));

        return $view;
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @param array $params
     * @return object
     */
    protected function preload(array $params)
    {
        $actor = app('flarum.actor');
        $action = 'Flarum\Api\Actions\Discussions\ShowAction';

        return $this->apiClient->send($actor, $action, $params)->getBody();
    }
}
