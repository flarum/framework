<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Events\DiscussionStateWillBeSaved;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\DispatchesEvents;

class ReadDiscussionCommandHandler
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

        if (! $user->exists) {
            throw new PermissionDeniedException;
        }

        $discussion = $this->discussions->findOrFail($command->discussionId, $user);

        $state = $discussion->stateFor($user);
        $state->read($command->readNumber);

        event(new DiscussionStateWillBeSaved($state, $command));

        $state->save();
        $this->dispatchEventsFor($state);

        return $state;
    }
}
