<?php namespace Flarum\Tags\Api;

use Flarum\Tags\Commands\EditTag;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class UpdateAction extends SerializeResourceAction
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
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Tags\Tag
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->bus->dispatch(
            new EditTag($request->get('id'), $request->actor, $request->get('data'))
        );
    }
}
