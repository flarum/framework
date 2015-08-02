<?php namespace Flarum\Tags\Api;

use Flarum\Tags\Commands\CreateTag;
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
    public $serializer = 'Flarum\Tags\Api\TagSerializer';

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Create a tag according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Tags\Tag
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new CreateTag($request->actor, $request->get('data'))
        );
    }
}
