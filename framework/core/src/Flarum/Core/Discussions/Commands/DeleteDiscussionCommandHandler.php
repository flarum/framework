<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Discussions\DiscussionRepository;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

class DeleteDiscussionCommandHandler implements CommandHandler
{
    use DispatchableTrait;

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

        Event::fire('Flarum.Core.Discussions.Commands.DeleteDiscussion.DiscussionWillBeDeleted', [$discussion, $command]);

        $this->discussions->delete($discussion);
        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
