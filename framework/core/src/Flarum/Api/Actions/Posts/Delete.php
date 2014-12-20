<?php namespace Flarum\Api\Actions\Posts;

use Event;
use Flarum\Core\Posts\Commands\DeletePostCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;

class Delete extends Base
{
    /**
     * Delete a post.
     *
     * @return Response
     */
    protected function run()
    {
        $postId = $this->param('id');
        $command = new DeletePostCommand($postId, User::current());

        Event::fire('Flarum.Api.Actions.Posts.Delete.WillExecuteCommand', [$command]);

        $this->commandBus->execute($command);

        return $this->respondWithoutContent();
    }
}
