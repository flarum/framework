<?php namespace Flarum\Forum\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;

class DiscussionAction extends ClientAction
{
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $actor = app('flarum.actor');
        $action = 'Flarum\Api\Actions\Discussions\ShowAction';
        $params = [
            'id' => $routeParams['id'],
            'page.near' => $routeParams['near']
        ];

        $document = $this->apiClient->send($actor, $action, $params)->getBody();

        $view->setTitle($document->data->attributes->title);
        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::discussion', compact('document')));

        return $view;
    }
}
