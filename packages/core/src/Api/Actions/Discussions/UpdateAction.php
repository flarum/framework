<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\EditDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\DiscussionSerializer;

class UpdateAction extends BaseAction
{
    /**
     * Edit a discussion. Allows renaming the discussion, and updating its read
     * state with regards to the current user.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $discussionId = $params->get('id');
        $user = $this->actor->getUser();

        // First, we will run the EditDiscussionCommand. This will update the
        // discussion's direct properties; by default, this is just the title.
        // As usual, however, we will fire an event to allow plugins to update
        // additional properties.
        if ($data = array_except($params->get('data'), ['readNumber'])) {
            try {
                $command = new EditDiscussionCommand($discussionId, $user);
                $this->hydrate($command, $params->get('data'));
                $discussion = $this->dispatch($command, $params);
            } catch (PermissionDeniedException $e) {
                // Temporary fix. See @todo below
                $discussion = \Flarum\Core\Models\Discussion::find($discussionId);
            }
        }

        // Next, if a read number was specified in the request, we will run the
        // ReadDiscussionCommand.
        //
        // @todo Currently, if the user doesn't have permission to edit a
        //     discussion, they're unable to update their readNumber because a
        //     PermissionDeniedException is thrown by the
        //     EditDiscussionCommand above. So this needs to be extracted into
        //     its own endpoint.
        if ($readNumber = $params->get('data.readNumber')) {
            $command = new ReadDiscussionCommand($discussionId, $user, $readNumber);
            $this->dispatch($command, $params);
        }

        // Presumably, the discussion was updated successfully. (One of the command
        // handlers would have thrown an exception if not.) We set this
        // discussion as our document's primary element.
        $serializer = new DiscussionSerializer(['addedPosts', 'addedPosts.user']);
        $document = $this->document()->setData($serializer->resource($discussion));

        return $this->respondWithDocument($document);
    }
}
