<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Commands\DeleteUserCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;

class DeleteAction extends BaseAction
{
    /**
     * Delete a user.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $userId = $params->get('id');

        $command = new DeleteUserCommand($userId, $this->actor->getUser());
        $this->dispatch($command, $params);

        return $this->respondWithoutContent();
    }
}
