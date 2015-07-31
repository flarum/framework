<?php namespace Flarum\Api\Actions\Groups;

use Flarum\Core\Groups\Group;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\GroupSerializer';

    /**
     * Get the groups, ready to be serialized and assigned to the document
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return Group::all();
    }
}
