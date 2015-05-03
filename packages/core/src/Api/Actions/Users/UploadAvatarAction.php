<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Commands\UploadAvatarCommand;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class UploadAvatarAction extends SerializeResourceAction
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
    public static $serializer = 'Flarum\Api\Serializers\UserSerializer';

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
     * Upload an avatar for a user, and return the user ready to be serialized
     * and assigned to the JsonApi response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\User
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        return $this->bus->dispatch(
            new UploadAvatarCommand($request->get('id'), $request->http->file('avatar'), $request->actor->getUser())
        );
    }
}
