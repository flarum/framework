<?php namespace Flarum\Api\Actions\Users;

use Event;

use Flarum\Core\Users\Commands\EditUserCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\UserSerializer;

class Update extends Base
{
    /**
     * Edit a user. Allows renaming the user, changing their email, and setting
     * their password.
     *
     * @return Response
     */
    protected function run()
    {
        $userId = $this->param('id');

        // EditUser is a single command because we don't want to allow partial
        // updates (i.e. if we were to run one command and then another, if the
        // second one failed, the first one would still have succeeded.)
        $command = new EditUserCommand($userId, User::current());
        $this->fillCommandWithInput($command, 'users');

        Event::fire('Flarum.Api.Actions.Users.Update.WillExecuteCommand', [$command]);

        $user = $this->commandBus->execute($command);

        // Presumably, the user was updated successfully. (The command handler
        // would have thrown an exception if not.) We set this user as our
        // document's primary element.
        $serializer = new UserSerializer;
        $this->document->setPrimaryElement($serializer->resource($user));

        return $this->respondWithDocument();
    }
}
