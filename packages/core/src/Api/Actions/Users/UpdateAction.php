<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Commands\EditUserCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Serializers\UserSerializer;

class UpdateAction extends BaseAction
{
    /**
     * Edit a user. Allows renaming the user, changing their email, and setting
     * their password.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $userId = $params->get('id');

        // EditUser is a single command because we don't want to allow partial
        // updates (i.e. if we were to run one command and then another, if the
        // second one failed, the first one would still have succeeded.)
        $command = new EditUserCommand($userId, $this->actor->getUser());
        $this->hydrate($command, $params->get('data'));
        $user = $this->dispatch($command, $params);

        // Presumably, the user was updated successfully. (The command handler
        // would have thrown an exception if not.) We set this user as our
        // document's primary element.
        $serializer = new UserSerializer;
        $document = $this->document()->setData($serializer->resource($user));

        return $this->respondWithDocument($document);
    }
}
