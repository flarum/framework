<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Discussions\DiscussionRepositoryInterface;
use Flarum\Core\Discussions\Events\DiscussionWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditDiscussionHandler
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
