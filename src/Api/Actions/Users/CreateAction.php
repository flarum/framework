<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Models\Forum;
use Flarum\Core\Commands\RegisterUserCommand;
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
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\UserSerializer';

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
     * Register a user according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Models\Model
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new RegisterUserCommand($request->actor->getUser(), $this->forum, $request->get('data'))
        );
    }
}
