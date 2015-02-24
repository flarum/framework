<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Events\DiscussionWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditDiscussionCommandHandler
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

        $discussion->assertCan($user, 'edit');

        if (isset($command->title)) {
            $discussion->rename($command->title, $user);
        }

        event(new DiscussionWillBeSaved($discussion, $command));

        $discussion->save();
        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
