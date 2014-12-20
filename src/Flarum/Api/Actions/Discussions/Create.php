<?php namespace Flarum\Api\Actions\Discussions;

use Event;

use Flarum\Core\Discussions\Commands\StartDiscussionCommand;
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
        $command = new StartDiscussionCommand($title, $content, User::current());

        Event::fire('Flarum.Api.Actions.Discussions.Create.WillExecuteCommand', [$command, $this->document]);

        $discussion = $this->commandBus->execute($command);

        $serializer = new DiscussionSerializer(['posts']);
        $this->document->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument();
    }
}
