<?php namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Commands\ReadNotificationCommand;
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
    public static $serializer = 'Flarum\Api\Serializers\NotificationSerializer';

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
     * Mark a notification as read, and return it ready to be serialized and
     * assigned to the JsonApi response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Notification
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        return $this->bus->dispatch(
            new ReadNotificationCommand($request->get('id'), $request->actor->getUser())
        );
    }
}
