<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\StartDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Core\Models\Forum;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
{
    /**
     * The command bus.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The default forum instance.
     *
     * @var \Flarum\Core\Models\Forum
     */
    protected $forum;

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
    public static $include = ['posts', 'startUser', 'lastUser', 'startPost', 'lastPost'];

    /**
     * Initialize the action.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher $bus
     * @param \Flarum\Core\Models\Forum $forum
     */
    public function __construct(Dispatcher $bus, Forum $forum)
    {
        $this->bus = $bus;
        $this->forum = $forum;
    }

    /**
     * Create a discussion according to input from the API request.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Discussion
     */
    protected function create(JsonApiRequest $request, JsonApiResponse $response)
    {
        $user = $request->actor->getUser();

        $discussion = $this->bus->dispatch(
            new StartDiscussionCommand($user, $this->forum, $request->get('data'))
        );

        // After creating the discussion, we assume that the user has seen all
        // of the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($user->exists) {
            $this->bus->dispatch(
                new ReadDiscussionCommand($discussion->id, $user, 1)
            );
        }

        return $discussion;
    }
}
