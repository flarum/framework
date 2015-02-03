<?php namespace Flarum\Api\Actions\Posts;

use Event;
use Flarum\Core\Posts\Commands\PostReplyCommand;
use Flarum\Core\Discussions\Commands\ReadDiscussionCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\PostSerializer;

class Create extends Base
{
    /**
     * Reply to a discussion.
     *
     * @return Response
     */
    protected function run()
    {
        $user = User::current();

        // We've received a request to post a reply. By default, the only
        // required attributes of a post is the ID of the discussion to post in,
        // the post content, and the author's user account. Let's set up a
        // command with this information. We also fire an event to allow plugins
        // to add data to the command.
        $discussionId = $this->input('posts.links.discussion');
        $content = $this->input('posts.content');
        $command = new PostReplyCommand($discussionId, $content, $user);

        Event::fire('Flarum.Api.Actions.Posts.Create.WillExecuteCommand', [$command]);

        $post = $this->commandBus->execute($command);

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($user->exists) {
            $command = new ReadDiscussionCommand($discussionId, $user, $post->number);
            $this->commandBus->execute($command);
        }

        // Presumably, the post was created successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new PostSerializer;
        $this->document->setPrimaryElement($serializer->resource($post));

        return $this->respondWithDocument(201);
    }
}
