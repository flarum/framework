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
use Flarum\Http\RequestUtil;
use Flarum\Post\Command\PostReply;
use Flarum\Post\CommentPost;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreatePostController extends AbstractCreateController
{
    public ?string $serializer = PostSerializer::class;

    public array $include = [
        'user',
        'discussion',
        'discussion.posts',
        'discussion.lastPostedUser'
    ];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): CommentPost
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);
        $discussionId = (int) Arr::get($data, 'relationships.discussion.data.id');
        $ipAddress = $request->getAttribute('ipAddress');

        /** @var CommentPost $post */
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
        $discussion->setRelation('posts', $discussion->posts()->whereVisibleTo($actor)->orderBy('created_at')->pluck('id'));

        $this->loadRelations($post->newCollection([$post]), $this->extractInclude($request), $request);

        return $post;
    }
}
