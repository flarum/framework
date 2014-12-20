<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Discussions\Discussion;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\DiscussionSerializer;

class Show extends Base
{
    /**
     * Show a single discussion.
     *
     * @return Response
     */
    protected function run()
    {
        $include = $this->included(['startPost', 'lastPost']);

        $discussion = Discussion::whereCanView()->findOrFail($this->param('id'));

        // Set up the discussion serializer, which we will use to create the
        // document's primary resource. As well as including the requested
        // relations, we will specify that we want the 'posts' relation to be
        // linked so that a list of post IDs will show up in the response.
        $serializer = new DiscussionSerializer($include, ['posts']);
        $this->document->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument();
    }
}
