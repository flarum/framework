<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Commands\DeleteDiscussionCommand;
use Flarum\Api\Actions\DeleteAction as BaseDeleteAction;
use Flarum\Api\Request;
use Illuminate\Http\Response;
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
     * Initialize the action.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Delete a discussion according to input from the API request.
     *
     * @param \Flarum\Api\Request $request
     * @return void
     */
    protected function delete(Request $request, Response $response)
    {
        $this->bus->dispatch(
            new DeleteDiscussionCommand($request->get('id'), $request->actor->getUser())
        );
    }
}
