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
use Flarum\Events\DiscussionStateWillBeSaved;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Support\DispatchesEvents;

class ReadDiscussionHandler
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
