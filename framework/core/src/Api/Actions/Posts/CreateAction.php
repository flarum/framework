<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\PostReplyCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Serializers\PostSerializer;

class CreateAction extends BaseAction
{
    /**
     * Reply to a discussion.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $user = $this->actor->getUser();

        // We've received a request to post a reply. By default, the only
        // required attributes of a post is the ID of the discussion to post in,
        // the post content, and the author's user account. Let's set up a
        // command with this information. We also fire an event to allow plugins
        // to add data to the command.
        $discussionId = $params->get('data.links.discussion.linkage.id');
        $content = $params->get('data.content');

        $command = new PostReplyCommand($discussionId, $content, $user);
        $post = $this->dispatch($command, $params);

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($user->exists) {
            $command = new ReadDiscussionCommand($discussionId, $user, $post->number);
            $this->dispatch($command, $params);
        }

        // Presumably, the post was created successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new PostSerializer;
        $document = $this->document()->setData($serializer->resource($post));

        return $this->respondWithDocument($document, 201);
    }
}
