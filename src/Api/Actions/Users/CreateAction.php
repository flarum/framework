<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Commands\RegisterUserCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Serializers\UserSerializer;

class CreateAction extends BaseAction
{
    /**
     * Register a user.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        // We've received a request to register a user. By default, the only
        // required attributes of a user is the username, email, and password.
        // Let's set up a command with this information. We also fire an event
        // to allow plugins to add data to the command.
        $username = $params->get('users.username');
        $email    = $params->get('users.email');
        $password = $params->get('users.password');

        $command = new RegisterUserCommand($username, $email, $password, $this->actor->getUser());
        $this->dispatch($command, $params);

        // Presumably, the user was created successfully. (The command handler
        // would have thrown an exception if not.) We set this post as our
        // document's primary element.
        $serializer = new UserSerializer;
        $document = $this->document()->setPrimaryElement($serializer->resource($user));

        return $this->respondWithDocument($document, 201);
    }
}
