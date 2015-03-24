<?php namespace Flarum\Core\Handlers\Commands;

use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Events\DiscussionWillBeSaved;
use Flarum\Core\Commands\PostReplyCommand;
use Flarum\Core\Support\DispatchesEvents;

class StartDiscussionCommandHandler
{
    use DispatchesEvents;

    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function handle($command)
    {
        $command->forum->assertCan($command->user, 'startDiscussion');

        // Create a new Discussion entity, persist it, and dispatch domain
        // events. Before persistance, though, fire an event to give plugins
        // an opportunity to alter the discussion entity based on data in the
        // command they may have passed through in the controller.
        $discussion = Discussion::start(
            $command->title,
            $command->user
        );

        event(new DiscussionWillBeSaved($discussion, $command));

        $discussion->save();

        $this->dispatchEventsFor($discussion);

        // Now that the discussion has been created, we can add the first post.
        // For now we will do this by running the PostReply command, but as this
        // will trigger a domain event that is slightly semantically incorrect
        // in this situation (PostWasPosted), we may need to reconsider someday.
        $post = $this->bus->dispatch(
            new PostReplyCommand($discussion->id, $command->content, $command->user)
        );

        // The discussion may have been updated by the PostReplyCommand; we need
        // to refresh its data.
        $discussion = $post->discussion;

        return $discussion;
    }
}
