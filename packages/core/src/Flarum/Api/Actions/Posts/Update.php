<?php namespace Flarum\Api\Actions\Posts;

use Event;

use Flarum\Core\Posts\Commands\EditPostCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\PostSerializer;

class Update extends Base
{
    /**
     * Edit a post. Allows revision of content, and hiding/unhiding.
     *
     * @return Response
     */
    protected function run()
    {
        $postId = $this->param('id');

        // EditPost is a single command because we don't want to allow partial
        // updates (i.e. if we were to run one command and then another, if the
        // second one failed, the first one would still have succeeded.)
        $command = new EditPostCommand($postId, User::current());
        $this->fillCommandWithInput($command, 'posts');

        Event::fire('Flarum.Api.Actions.Posts.Update.WillExecuteCommand', [$command]);

        $post = $this->commandBus->execute($command);

        // Presumably, the post was updated successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new PostSerializer;
        $this->document->setPrimaryElement($serializer->resource($post));

        return $this->respondWithDocument();
    }
}
