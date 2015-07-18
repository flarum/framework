<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Events\DiscussionWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditDiscussionHandler
{
    use DispatchesEvents;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @param DiscussionRepository $discussions
     */
    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * @param EditDiscussion $command
     * @return \Flarum\Core\Discussions\Discussion
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(EditDiscussion $command)
    {
        $actor = $command->actor;
        $data = $command->data;
        $attributes = array_get($data, 'attributes', []);

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        if (isset($attributes['title'])) {
            $discussion->assertCan($actor, 'rename');
            $discussion->rename($attributes['title'], $actor);
        }

        event(new DiscussionWillBeSaved($discussion, $actor, $data));

        $discussion->save();

        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
