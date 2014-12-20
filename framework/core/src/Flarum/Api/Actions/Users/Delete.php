<?php namespace Flarum\Api\Actions\Users;

use Event;
use Flarum\Core\Users\User;
use Flarum\Core\Users\Commands\DeleteUserCommand;
use Flarum\Api\Actions\Base;

class Delete extends Base
{
    /**
     * Delete a user.
     *
     * @return Response
     */
    protected function run()
    {
        $userId = $this->param('id');
        $command = new DeleteUserCommand($userId, User::current());

        Event::fire('Flarum.Api.Actions.Users.Delete.WillExecuteCommand', [$command]);

        $this->commandBus->execute($command);

        return $this->respondWithoutContent();
    }
}
