<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Discussions\DiscussionRepositoryInterface;
use Flarum\Core\Posts\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    use GetsPosts;

    /**
     * @var \Flarum\Core\Discussions\DiscussionRepositoryInterface
     */
    protected $discussions;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
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
    public static $link = ['posts', 'posts.discussion'];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 20;

    /**
     * @inheritdoc
     */
    public static $sortFields = ['time'];

    /**
     * @inheritdoc
     */
    public static $sort = ['time' => 'asc'];

    /**
     * Instantiate the action.
     *
     * @param DiscussionRepositoryInterface $discussions
     * @param PostRepositoryInterface $posts
     */
    public function __construct(DiscussionRepositoryInterface $discussions, PostRepositoryInterface $posts)
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
