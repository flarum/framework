<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Repositories\DiscussionRepositoryInterface;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\Actions\Posts\GetsPosts;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    use GetsPosts;

    /**
     * @var \Flarum\Core\Repositories\DiscussionRepositoryInterface
     */
    protected $discussions;

    /**
     * @var \Flarum\Core\Repositories\PostRepositoryInterface
     */
    protected $posts;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'startUser' => false,
        'lastUser' => false,
        'startPost' => true,
        'lastPost' => true,
        'posts' => true,
        'posts.user' => true,
        'posts.user.groups' => true,
        'posts.editUser' => true,
        'posts.hideUser' => true
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
    public static $sortFields = ['time'];

    /**
     * The default sort field and order to user.
     *
     * @var string
     */
    public static $sort = ['time' => 'asc'];

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Repositories\DiscussionRepositoryInterface $discussions
     * @param \Flarum\Core\Repositories\PostRepositoryInterface $posts
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
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
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
