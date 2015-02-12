<?php namespace Flarum\Api\Actions\Discussions;

use Event;

use Flarum\Core\Discussions\Commands\StartDiscussionCommand;
use Flarum\Core\Discussions\Commands\ReadDiscussionCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\DiscussionSerializer;

class Create extends Base
{
    /**
     * Start a new discussion.
     *
     * @return Response
     */
    protected function run()
    {
        // By default, the only required attributes of a discussion are the
        // title and the content. We'll extract these from the request data
        // and pass them through to the StartDiscussionCommand.
        $title = $this->input('discussions.title');
        $content = $this->input('discussions.content');
        $user = User::current();
        $command = new StartDiscussionCommand($title, $content, $user);

        Event::fire('Flarum.Api.Actions.Discussions.Create.WillExecuteCommand', [$command, $this->document]);

        $discussion = $this->commandBus->execute($command);

        // After creating the discussion, we assume that the user has seen all
        // of the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($user->exists) {
            $command = new ReadDiscussionCommand($discussion->id, $user, 1);
            $this->commandBus->execute($command);
        }

        $serializer = new DiscussionSerializer(['posts']);
        $this->document->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument();
    }
}
