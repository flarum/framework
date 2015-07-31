<?php namespace Flarum\Api\Actions\Groups;

use Flarum\Core\Groups\Commands\DeleteGroup;
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
     * Delete a group.
     *
     * @param Request $request
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeleteGroup($request->get('id'), $request->actor)
        );
    }
}
