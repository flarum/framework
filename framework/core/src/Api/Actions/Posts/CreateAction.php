<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Posts\Commands\PostReply;
use Flarum\Core\Discussions\Commands\ReadDiscussion;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
{
    /**
     * @var Dispatcher
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
        'user' => true,
        'discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public static $link = ['discussion.posts'];

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
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Reply to a discussion according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Posts\Post
     */
    protected function create(JsonApiRequest $request)
    {
        $actor = $request->actor;
        $discussionId = $request->get('data.relationships.discussion.data.id');

        $post = $this->bus->dispatch(
            new PostReply($discussionId, $actor, $request->get('data'))
        );

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $post->number)
            );
        }

        $discussion = $post->discussion;
        $discussion->posts_ids = $discussion->postsVisibleTo($actor)->orderBy('time')->lists('id');

        return $post;
    }
}
