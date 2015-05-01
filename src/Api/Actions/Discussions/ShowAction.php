<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Repositories\DiscussionRepositoryInterface;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

class ShowAction extends SerializeResourceAction
{
    use GetsPosts;

    /**
     * The discussion repository.
     *
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * The post repository.
     *
     * @var PostRepository
     */
    protected $posts;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * The relations that are available to be included.
     *
     * @var array
     */
    public static $includeAvailable = [
        'startUser', 'lastUser', 'startPost', 'lastPost', 'posts',
        'posts.user', 'posts.user.groups', 'posts.editUser', 'posts.hideUser'
    ];

    /**
     * The relations that are included by default.
     *
     * @var array
     */
    public static $include = [
        'startPost', 'lastPost', 'posts',
        'posts.user', 'posts.user.groups', 'posts.editUser', 'posts.hideUser'
    ];

    /**
     * The relations that are linked by default.
     *
     * @var array
     */
    public static $link = ['posts'];

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public static $sortAvailable = ['time'];

    /**
     * The default field to sort by.
     *
     * @var string
     */
    public static $sort = ['time' => 'asc'];

    /**
     * The maximum number of records that can be requested.
     *
     * @var integer
     */
    public static $limitMax = 50;

    /**
     * The number of records included by default.
     *
     * @var integer
     */
    public static $limit = 20;

    /**
     * Instantiate the action.
     *
     * @param PostRepository $posts
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
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Flarum\Core\Models\Discussion
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $user = $request->actor->getUser();

        $discussion = $this->discussions->findOrFail($request->get('id'), $user);

        $discussion->posts_ids = $discussion->posts()->whereCan($user, 'view')->get(['id'])->fetch('id')->all();

        if (in_array('posts', $request->include)) {
            $length = strlen($prefix = 'posts.');
            $relations = array_filter(array_map(function ($relationship) use ($prefix, $length) {
                return substr($relationship, 0, $length) === $prefix ? substr($relationship, $length) : false;
            }, $request->include));

            $discussion->posts = $this->getPosts($request, ['discussion_id' => $discussion->id])->load($relations);
        }

        return $discussion;
    }
}
