<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\StartDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Core\Models\Forum;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
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
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'posts' => true,
        'startUser' => true,
        'lastUser' => true,
        'startPost' => true,
        'lastPost' => true
    ];

    /**
     * @inheritdoc
     */
    public static $link = [];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 20;

    /**
     * @inheritdoc
     */
    public static $sortFields = [];

    /**
     * @inheritdoc
     */
    public static $sort;

    /**
     * Instantiate the action.
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
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Models\Model
     */
    protected function create(JsonApiRequest $request)
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
