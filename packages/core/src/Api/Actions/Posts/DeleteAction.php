<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Commands\DeletePostCommand;
use Flarum\Api\Actions\DeleteAction as BaseDeleteAction;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;

class DeleteAction extends BaseDeleteAction
{
    /**
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
     * Delete a post.
     *
     * @param \Flarum\Api\Request $request
     * @return void
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeletePostCommand($request->get('id'), $request->actor->getUser())
        );
    }
}
