<?php namespace Flarum\Core\Discussions\Commands;

use Laracasts\Commander\CommandBus;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Forum;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\Commands\PostReplyCommand;

class StartDiscussionCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $forum;

    protected $discussionRepo;

    protected $commandBus;

    public function __construct(Forum $forum, DiscussionRepository $discussionRepo, CommandBus $commandBus)
    {
        $this->forum = $forum;
        $this->discussionRepo = $discussionRepo;
        $this->commandBus = $commandBus;
    }

    public function handle($command)
    {
        $this->forum->assertCan($command->user, 'startDiscussion');
        
        // Create a new Discussion entity, persist it, and dispatch domain
        // events. Before persistance, though, fire an event to give plugins
        // an opportunity to alter the discussion entity based on data in the
        // command they may have passed through in the controller.
        $discussion = Discussion::start(
            $command->title,
            $command->user
        );

        Event::fire('Flarum.Core.Discussions.Commands.StartDiscussion.DiscussionWillBeSaved', [$discussion, $command]);

        $this->discussionRepo->save($discussion);

        // Now that the discussion has been created, we can add the first post.
        // For now we will do this by running the PostReply command, but as this
        // will trigger a domain event that is slightly semantically incorrect
        // in this situation (ReplyWasPosted), we may need to reconsider someday.
        $this->commandBus->execute(
            new PostReplyCommand($discussion->id, $command->content, $command->user)
        );

        $this->dispatchEventsFor($discussion);
        
        return $discussion;
    }
}
