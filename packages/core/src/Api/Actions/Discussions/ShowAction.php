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

use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\PostRepository;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    use GetsPosts;

    /**
     * @var \Flarum\Core\Discussions\DiscussionRepository
     */
    protected $discussions;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'startUser' => false,
        'lastUser' => false,
        'startPost' => false,
        'lastPost' => false,
        'posts' => true,
        'posts.user' => true,
        'posts.user.groups' => true,
        'posts.editUser' => true,
        'posts.hideUser' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = ['posts', 'posts.discussion'];

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
    public $sortFields = ['time'];

    /**
     * @inheritdoc
     */
    public $sort = ['time' => 'asc'];

    /**
     * Instantiate the action.
     *
     * @param DiscussionRepository $discussions
     * @param PostRepository $posts
     */
    public function __construct(DiscussionRepository $discussions, PostRepository $posts)
    {
        $this->discussions = $discussions;
        $this->posts = $posts;
    }

    /**
     * Get a single discussion, ready to be serialized and assigned to the
     * JsonApi response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Discussions\Discussion
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $discussionId = $request->get('id');
        $actor = $request->actor;

        $discussion = $this->discussions->findOrFail($discussionId, $actor);

        $discussion->posts_ids = $discussion->postsVisibleTo($actor)->orderBy('time')->lists('id');

        // TODO: Refactor to be simpler, and get posts straight from the
        // discussion's postsVisibleTo relation method.
        if (in_array('posts', $request->include)) {
            $prefixLength = strlen($prefix = 'posts.');

            $postRelations = array_filter(array_map(function ($relation) use ($prefix, $prefixLength) {
                return substr($relation, 0, $prefixLength) === $prefix ? substr($relation, $prefixLength) : false;
            }, $request->include));

            $discussion->posts = $this->getPosts($request, ['discussion_id' => $discussion->id])->load($postRelations);
        }

        return $discussion;
    }
}
