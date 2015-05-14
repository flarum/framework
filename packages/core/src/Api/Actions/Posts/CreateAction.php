<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\PostReplyCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\PostSerializer';

    public static $include = [
        'user' => true
    ];

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
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Post
     */
    protected function create(JsonApiRequest $request, JsonApiResponse $response)
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
