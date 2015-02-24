<?php namespace Flarum\Api\Actions\Groups;

use Flarum\Core\Models\Group;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\GroupSerializer;

class Index extends Base
{
    protected function run()
    {
        $groups = Group::get();

        $serializer = new GroupSerializer;
        $this->document->setPrimaryElement($serializer->collection($groups));

        return $this->respondWithDocument();
    }
}
