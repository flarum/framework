<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\PostReplyCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\PostSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'user' => true
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
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Reply to a discussion according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Models\Model
     */
    protected function create(JsonApiRequest $request)
    {
        $user = $request->actor->getUser();

        $discussionId = $request->get('data.links.discussion.linkage.id');

        $post = $this->bus->dispatch(
            new PostReplyCommand($discussionId, $user, $request->get('data'))
        );

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($user->exists) {
            $this->bus->dispatch(
                new ReadDiscussionCommand($discussionId, $user, $post->number)
            );
        }

        return $post;
    }
}
