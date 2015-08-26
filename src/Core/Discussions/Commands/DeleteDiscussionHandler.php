<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Events\DiscussionWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteDiscussionHandler
{
    use DispatchesEvents;

    /**
     * @var \Flarum\Core\Discussions\DiscussionRepository
     */
    protected $discussions;

    /**
     * @param \Flarum\Core\Discussions\DiscussionRepository $discussions
     */
    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * @param \Flarum\Core\Discussions\Commands\DeleteDiscussion $command
     * @return \Flarum\Core\Discussions\Discussion
     */
    public function handle(DeleteDiscussion $command)
    {
        $actor = $command->actor;

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $discussion->assertCan($actor, 'delete');

        event(new DiscussionWillBeDeleted($discussion, $actor, $command->data));

        $discussion->delete();

        $this->dispatchEventsFor($discussion);

        return $discussion;
    }
}
