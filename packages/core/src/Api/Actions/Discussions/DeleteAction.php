<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\DeleteDiscussionCommand;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;

class DeleteAction extends BaseAction
{
    /**
     * Delete a discussion.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $discussionId = $params->get('id');

        $command = new DeleteDiscussionCommand($discussionId, $this->actor->getUser());
        $this->dispatch($command, $params);

        return $this->respondWithoutContent();
    }
}
