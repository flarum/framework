<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\EditPostCommand;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Bus\Dispatcher;

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
    public static $serializer = 'Flarum\Api\Serializers\PostSerializer';

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
     * Update a post according to input from the API request, and return it
     * ready to be serialized and assigned to the JsonApi response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Post
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        return $this->bus->dispatch(
            new EditPostCommand($request->get('id'), $request->actor->getUser(), $request->get('data'))
        );
    }
}
