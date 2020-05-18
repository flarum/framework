<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Discussion\Command\ReadDiscussion;
use Flarum\Post\Command\PostReply;
use Flarum\Post\Floodgate;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreatePostController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = PostSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'discussion',
        'discussion.posts',
        'discussion.lastPostedUser'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var \Flarum\Post\Floodgate
     */
    protected $floodgate;

    /**
     * @param Dispatcher $bus
     * @param \Flarum\Post\Floodgate $floodgate
     */
    public function __construct(Dispatcher $bus, Floodgate $floodgate)
    {
        $this->bus = $bus;
        $this->floodgate = $floodgate;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $data = Arr::get($request->getParsedBody(), 'data', []);
        $discussionId = Arr::get($data, 'relationships.discussion.data.id');
        $ipAddress = Arr::get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        /**
         * @deprecated, remove in beta 15.
         */
        if (! $request->getAttribute('bypassFloodgate')) {
            $this->floodgate->assertNotFlooding($actor);
        }

        $post = $this->bus->dispatch(
            new PostReply($discussionId, $actor, $data, $ipAddress)
        );

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $post->number)
            );
        }

        $discussion = $post->discussion;
        $discussion->posts = $discussion->posts()->whereVisibleTo($actor)->orderBy('created_at')->pluck('id');

        return $post;
    }
}
