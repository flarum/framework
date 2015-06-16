<?php namespace Flarum\Api\Actions\Forum;

use Flarum\Core\Models\Forum;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

class ShowAction extends SerializeResourceAction
{
    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\ForumSerializer';

    /**
     * Get the forum, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Forum
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        return app('flarum.forum');
    }
}
