<?php namespace Flarum\Api\Actions\Users;

use Event;
use Flarum\Core\Users\Commands\RegisterUserCommand;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\UserSerializer;

class Create extends Base
{
    /**
     * Register a user.
     *
     * @return Response
     */
    protected function run()
    {
        // We've received a request to register a user. By default, the only
        // required attributes of a user is the username, email, and password.
        // Let's set up a command with this information. We also fire an event
        // to allow plugins to add data to the command.
        $username = $this->input('users.username');
        $email    = $this->input('users.email');
        $password = $this->input('users.password');
        $command  = new RegisterUserCommand($username, $email, $password, User::current());

        Event::fire('Flarum.Api.Actions.Users.Create.WillExecuteCommand', [$command]);

        $user = $this->commandBus->execute($command);

        // Presumably, the user was created successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new UserSerializer;
        $this->document->setPrimaryElement($serializer->resource($user));

        return $this->respondWithDocument(201);
    }
}
