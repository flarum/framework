<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\StartDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\DiscussionSerializer;

class CreateAction extends BaseAction
{
    /**
     * Start a new discussion.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        // By default, the only required attributes of a discussion are the
        // title and the content. We'll extract these from the rbaseequest data
        // and pass them through to the StartDiscussionCommand.
        $title = $params->get('discussions.title');
        $content = $params->get('discussions.content');
        $user = $this->actor->getUser();

        $command = new StartDiscussionCommand($title, $content, $user, app('flarum.forum'));
        $discussion = $this->dispatch($command, $params);

        // After creating the discussion, we assume that the user has seen all
        // of the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($user->exists) {
            $command = new ReadDiscussionCommand($discussion->id, $user, 1);
            $this->dispatch($command, $params);
        }

        $serializer = new DiscussionSerializer(['posts']);
        $document = $this->document()->setPrimaryElement($serializer->resource($discussion));

        return $this->respondWithDocument($document);
    }
}
