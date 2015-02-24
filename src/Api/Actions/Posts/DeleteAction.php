<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\DeletePostCommand;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;

class DeleteAction extends BaseAction
{
    /**
     * Delete a post.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $postId = $params->get('id');

        $command = new DeletePostCommand($postId, $this->actor->getUser());
        $this->dispatch($command, $params);

        return $this->respondWithoutContent();
    }
}
