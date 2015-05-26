<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\EditDiscussionCommand;
use Flarum\Core\Commands\ReadDiscussionCommand;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class UpdateAction extends SerializeResourceAction
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
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * The relations that are included by default.
     *
     * @var array
     */
    public static $include = [
        'addedPosts' => true,
        'addedPosts.user' => true
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
     * Update a discussion according to input from the API request, and return
     * it ready to be serialized and assigned to the JsonApi response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $user = $request->actor->getUser();
        $discussionId = $request->get('id');

        if ($data = array_except($request->get('data'), ['readNumber'])) {
            $discussion = $this->bus->dispatch(
                new EditDiscussionCommand($discussionId, $user, $data)
            );
        }

        if ($readNumber = $request->get('data.readNumber')) {
            $state = $this->bus->dispatch(
                new ReadDiscussionCommand($discussionId, $user, $readNumber)
            );

            $discussion = $state->discussion;
        }

        return $discussion;
    }
}
