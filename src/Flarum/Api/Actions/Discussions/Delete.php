<?php namespace Flarum\Api\Actions\Discussions;

use Event;
use Flarum\Core\Discussions\Commands\DeleteDiscussionCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;

class Delete extends Base
{
    /**
     * Delete a discussion.
     *
     * @return Response
     */
    protected function run()
    {
        $discussionId = $this->param('id');
        $command = new DeleteDiscussionCommand($discussionId, User::current());

        Event::fire('Flarum.Api.Actions.Discussions.Delete.WillExecuteCommand', [$command]);

        $this->commandBus->execute($command);

        return $this->respondWithoutContent();
    }
}
