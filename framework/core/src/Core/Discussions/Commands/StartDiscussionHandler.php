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

use Flarum\Events\DiscussionWillBeSaved;
use Flarum\Core\Forum;
use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Posts\Commands\PostReply;
use Flarum\Core\Support\DispatchesEvents;
use Exception;

class StartDiscussionHandler
{
    use DispatchesEvents;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Forum
     */
    protected $forum;

    /**
     * @param Dispatcher $bus
     * @param Forum $forum
     */
    public function __construct(Dispatcher $bus, Forum $forum)
    {
        $this->bus = $bus;
        $this->forum = $forum;
    }

    /**
     * @param StartDiscussion $command
     * @return mixed
     */
    public function handle(StartDiscussion $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $this->forum->assertCan($actor, 'startDiscussion');

        // Create a new Discussion entity, persist it, and dispatch domain
        // events. Before persistance, though, fire an event to give plugins
        // an opportunity to alter the discussion entity based on data in the
        // command they may have passed through in the controller.
        $discussion = Discussion::start(
            array_get($data, 'attributes.title'),
            $actor
        );

        event(new DiscussionWillBeSaved($discussion, $actor, $data));

        $discussion->save();

        // Now that the discussion has been created, we can add the first post.
        // We will do this by running the PostReply command.
        try {
            $post = $this->bus->dispatch(
                new PostReply($discussion->id, $actor, $data)
            );
        } catch (Exception $e) {
            $discussion->delete();

            throw $e;
        }

        // Before we dispatch events, refresh our discussion instance's
        // attributes as posting the reply will have changed some of them (e.g.
        // last_time.)
        $discussion->setRawAttributes($post->discussion->getAttributes(), true);
        $discussion->setStartPost($post);

        $this->dispatchEventsFor($discussion);

        $discussion->save();

        return $discussion;
    }
}
