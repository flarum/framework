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
            array_get($command->data, 'title'),
            $command->user
        );

        event(new DiscussionWillBeSaved($discussion, $command));

        $discussion->save();

        // Now that the discussion has been created, we can add the first post.
        // We will do this by running the PostReply command.
        $post = $this->bus->dispatch(
            new PostReplyCommand($discussion->id, $command->user, $command->data)
        );

        // Before we dispatch events, refresh our discussion instance's
        // attributes as posting the reply will have changed some of them (e.g.
        // last_time.)
        $discussion->setRawAttributes($post->discussion->getAttributes(), true);

        $this->dispatchEventsFor($discussion);

        return $post->discussion;
    }
}
