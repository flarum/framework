<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Events\DiscussionWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteDiscussionCommandHandler
{
    use DispatchesEvents;

    protected $discussions;

    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    public function handle($command)
    {
        $user = $command->user;
        $discussion = $this->discussions->findOrFail($command->discussionId, $user);

        $discussion->assertCan($user, 'delete');

        event(new DiscussionWillBeDeleted($discussion, $command));

        $discussion->delete();
        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
