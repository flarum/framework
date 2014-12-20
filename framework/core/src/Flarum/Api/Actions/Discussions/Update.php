<?php namespace Flarum\Api\Actions\Discussions;

use Event;

use Flarum\Core\Discussions\Commands\EditDiscussionCommand;
use Flarum\Core\Discussions\Commands\ReadDiscussionCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\DiscussionSerializer;

class Update extends Base
{
    /**
     * Edit a discussion. Allows renaming the discussion, and updating its read
     * state with regards to the current user.
     *
     * @return Response
     */
    protected function run()
    {
        $discussionId = $this->param('id');
        $readNumber = $this->input('discussions.readNumber');

        // First, we will run the EditDiscussionCommand. This will update the
        // discussion's direct properties; by default, this is just the title.
        // As usual, however, we will fire an event to allow plugins to update
        // additional properties.
        $command = new EditDiscussionCommand($discussionId, User::current());
        $this->fillCommandWithInput($command, 'discussions');

        Event::fire('Flarum.Api.Actions.Discussions.Update.WillExecuteCommand', [$command]);

        $discussion = $this->commandBus->execute($command);

        // Next, if a read number was specified in the request, we will run the
        // ReadDiscussionCommand. We won't bother firing an event for this one,
        // because it's pretty specific. (This may need to change in the future.)
        if ($readNumber) {
            $command = new ReadDiscussionCommand($discussionId, User::current(), $readNumber);
            $discussion = $this->commandBus->execute($command);
        }

        // Presumably, the discussion was updated successfully. (One of the command
        // handlers would have thrown an exception if not.) We set this
        // discussion as our document's primary element.
        $serializer = new DiscussionSerializer;
        $this->document->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument();
    }
}
