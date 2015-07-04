<?php namespace Flarum\Forum\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;

class DiscussionAction extends IndexAction
{
    protected function getDetails(Request $request, array $routeParams)
    {
        $response = $this->apiClient->send(app('flarum.actor'), 'Flarum\Api\Actions\Discussions\ShowAction', [
            'id' => $routeParams['id'],
            'near' => $routeParams['near']
        ]);

        // TODO: return an object instead of an array?
        return [
            'title' => $response->data->attributes->title,
            'response' => $response
        ];
    }
}
