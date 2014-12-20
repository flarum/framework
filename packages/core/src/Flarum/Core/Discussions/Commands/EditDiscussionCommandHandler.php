<?php namespace Flarum\Core\Discussions\Commands;

use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

use Flarum\Core\Discussions\DiscussionRepository;

class EditDiscussionCommandHandler implements CommandHandler
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

        $discussion->assertCan($user, 'edit');

        if (isset($command->title)) {
            $discussion->rename($command->title, $user);
        }
        
        Event::fire('Flarum.Core.Discussions.Commands.EditDiscussion.DiscussionWillBeSaved', [$discussion, $command]);

        $this->discussions->save($discussion);
        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
