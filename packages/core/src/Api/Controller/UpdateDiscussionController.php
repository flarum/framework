<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Command\EditDiscussion;
use Flarum\Discussion\Command\ReadDiscussion;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateDiscussionController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $discussionId = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data', []);

        $discussion = $this->bus->dispatch(
            new EditDiscussion($discussionId, $actor, $data)
        );

        // TODO: Refactor the ReadDiscussion (state) command into EditDiscussion?
        // That's what extensions will do anyway.
        if ($readNumber = Arr::get($data, 'attributes.lastReadPostNumber')) {
            $state = $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $readNumber)
            );

            $discussion = $state->discussion;
        }

        if ($posts = $discussion->getModifiedPosts()) {
            $posts = (new Collection($posts))->load('discussion', 'user');
            $discussionPosts = $discussion->posts()->whereVisibleTo($actor)->oldest()->pluck('id')->all();

            foreach ($discussionPosts as &$id) {
                foreach ($posts as $post) {
                    if ($id == $post->id) {
                        $id = $post;
                    }
                }
            }

            $discussion->setRelation('posts', $discussionPosts);

            $this->include = array_merge($this->include, ['posts', 'posts.discussion', 'posts.user']);
        }

        return $discussion;
    }
}
