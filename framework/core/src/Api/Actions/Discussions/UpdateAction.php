<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Discussions\Commands\EditDiscussion;
use Flarum\Core\Discussions\Commands\ReadDiscussion;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class UpdateAction extends SerializeResourceAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * @inheritdoc
     */
    public $include = [];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Update a discussion according to input from the API request, and return
     * it ready to be serialized and assigned to the JsonApi response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Discussions\Discussion
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $actor = $request->actor;
        $discussionId = $request->get('id');
        $data = $request->get('data');

        $discussion = $this->bus->dispatch(
            new EditDiscussion($discussionId, $actor, $data)
        );

        // TODO: Refactor the ReadDiscussion (state) command into EditDiscussion?
        // That's what extensions will do anyway.
        if ($readNumber = array_get($data, 'attributes.readNumber')) {
            $state = $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $readNumber)
            );

            $discussion = $state->discussion;
        }

        if ($posts = $discussion->getModifiedPosts()) {
            $discussion->posts_ids = $discussion->postsVisibleTo($actor)->orderBy('time')->lists('id');

            $discussion->posts = array_filter($posts, function ($post) {
                return $post->exists;
            });

            $request->include = array_merge($request->include, ['posts']);
            $request->link = array_merge($request->include, ['posts', 'posts.discussion', 'posts.user']);
        }

        return $discussion;
    }
}
