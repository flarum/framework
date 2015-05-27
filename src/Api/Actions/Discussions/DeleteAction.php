<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\DeleteDiscussionCommand;
use Flarum\Api\Actions\DeleteAction as BaseDeleteAction;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;

class DeleteAction extends BaseDeleteAction
{
    /**
     * The command bus.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

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
     * Delete a discussion.
     *
     * @param \Flarum\Api\Request $request
     * @return void
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeleteDiscussionCommand($request->get('id'), $request->actor->getUser())
        );
    }
}
