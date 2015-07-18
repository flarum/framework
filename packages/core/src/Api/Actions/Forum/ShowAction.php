<?php namespace Flarum\Api\Actions\Forum;

use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\ForumSerializer';

    /**
     * @inheritdoc
     */
    public $include = [];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * Get the forum, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Forum
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return app('flarum.forum');
    }
}
