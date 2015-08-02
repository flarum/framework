<?php namespace Flarum\Tags\Api;

use Flarum\Tags\Commands\DeleteTag;
use Flarum\Api\Actions\DeleteAction as BaseDeleteAction;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;

class DeleteAction extends BaseDeleteAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Delete a tag.
     *
     * @param Request $request
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeleteTag($request->get('id'), $request->actor)
        );
    }
}
