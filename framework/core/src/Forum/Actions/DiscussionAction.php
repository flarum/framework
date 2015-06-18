<?php namespace Flarum\Forum\Actions;

class DiscussionAction extends IndexAction
{
    protected function getDetails($request, $params)
    {
        $response = $this->apiClient->send('Flarum\Api\Actions\Discussions\ShowAction', [
            'id' => $params['id'],
            'near' => $params['near']
        ]);

        // TODO: return an object instead of an array?
        return [
            'title' => $response->data->title,
            'response' => $response
        ];
    }
}
