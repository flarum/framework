<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\EditPostCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Serializers\PostSerializer;

class UpdateAction extends BaseAction
{
    /**
     * Edit a post. Allows revision of content, and hiding/unhiding.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $postId = $params->get('id');

        // EditPost is a single command because we don't want to allow partial
        // updates (i.e. if we were to run one command and then another, if the
        // second one failed, the first one would still have succeeded.)
        $command = new EditPostCommand($postId, $this->actor->getUser());
        $this->hydrate($command, $params->get('data'));
        $post = $this->dispatch($command, $params);

        // Presumably, the post was updated successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new PostSerializer;
        $document = $this->document()->setData($serializer->resource($post));

        return $this->respondWithDocument($document);
    }
}
