<?php namespace Flarum\Core\Discussions\Commands;

use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Discussions\DiscussionRepository;

class ReadDiscussionCommandHandler implements CommandHandler
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

        $discussion->state = $this->discussions->getState($discussion, $user);
        $discussion->state->read($command->readNumber);
        
        Event::fire('Flarum.Core.Discussions.Commands.ReadDiscussion.StateWillBeSaved', [$discussion, $command]);

        $this->discussions->saveState($discussion->state);
        $this->dispatchEventsFor($discussion->state);

        return $discussion;
    }
}
