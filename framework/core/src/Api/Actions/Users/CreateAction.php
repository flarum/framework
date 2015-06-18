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
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [];

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
