<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\EditDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateAction extends SerializeResourceAction
{
    /**
     * The command bus.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * The relations that are included by default.
     *
     * @var array
     */
    public static $include = ['addedPosts', 'addedPosts.user'];

    /**
     * Initialize the action.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Update a discussion according to input from the API request, and return
     * it ready to be serialized and assigned to the JsonApi response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Flarum\Core\Models\Discussion
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $user = $request->actor->getUser();
        $discussionId = $request->get('id');

        // First, we will run the EditDiscussionCommand. This will update the
        // discussion's direct properties; by default, this is just the title.
        // As usual, however, we will fire an event to allow plugins to update
        // additional properties.
        if ($data = array_except($request->get('data'), ['readNumber'])) {
            $discussion = $this->bus->dispatch(
                new EditDiscussionCommand($discussionId, $user, $data)
            );
        }

        // Next, if a read number was specified in the request, we will run the
        // ReadDiscussionCommand.
        if ($readNumber = $request->get('data.readNumber')) {
            $state = $this->bus->dispatch(
                new ReadDiscussionCommand($discussionId, $user, $readNumber)
            );

            $discussion = $state->discussion;
        }

        return $discussion;
    }
}
