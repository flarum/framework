<?php namespace Flarum\Api\Actions\Posts;

use Flarum\Core\Posts\Commands\DeletePost;
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
     * Delete a post.
     *
     * @param Request $request
     * @return void
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeletePost($request->get('id'), $request->actor)
        );
    }
}
