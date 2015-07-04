<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Discussions\DiscussionRepositoryInterface;
use Flarum\Core\Discussions\Events\DiscussionStateWillBeSaved;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\DispatchesEvents;

class ReadDiscussionHandler
{
    use DispatchesEvents;

    /**
     * @var DiscussionRepositoryInterface
     */
    protected $discussions;

    /**
     * @param DiscussionRepositoryInterface $discussions
     */
    public function __construct(DiscussionRepositoryInterface $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * @param ReadDiscussion $command
     * @return \Flarum\Core\Discussions\DiscussionState
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(ReadDiscussion $command)
    {
        $actor = $command->actor;

        if (! $actor->exists) {
            throw new PermissionDeniedException;
        }

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $state = $discussion->stateFor($actor);
        $state->read($command->readNumber);

        event(new DiscussionStateWillBeSaved($state));

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
