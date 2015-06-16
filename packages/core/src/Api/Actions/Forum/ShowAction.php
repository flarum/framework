<?php namespace Flarum\Api\Actions\Forum;

use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

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
     * @param \Tobscure\JsonApi\Document $document
     * @return array
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return app('flarum.forum');
    }
}
