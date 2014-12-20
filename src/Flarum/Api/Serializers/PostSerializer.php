<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;

class PostSerializer extends PostBasicSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'Post';

    /**
     * Default relations to link.
     * @var array
     */
    protected $link = ['discussion'];

    /**
     * Default relations to include.
     * @var array
     */
    protected $include = ['user', 'editUser', 'deleteUser'];

    /**
     * Serialize attributes of a Post model for JSON output.
     *
     * @param  Post  $post The Post model to serialize.
     * @return array
     */
    protected function attributes(Post $post)
    {
        $attributes = parent::attributes($post);

        unset($attributes['content']);
        if ($post->type != 'comment') {
            $attributes['content'] = $post->content;
        } else {
            // @todo move to a formatter class
            $attributes['contentHtml'] = $post->content_html ?: '<p>'.nl2br(htmlspecialchars(trim($post->content))).'</p>';
        }

        if ($post->edit_time) {
            $attributes['editTime'] = (string) $post->edit_time;
        }

        if ($post->delete_time) {
            $attributes['deleteTime'] = (string) $post->delete_time;
        }

        $user = User::current();

        $attributes += [
            'canEdit'   => $post->can($user, 'edit'),
            'canDelete' => $post->can($user, 'delete')
        ];

        return $this->attributesEvent($post, $attributes);
    }

    /**
     * Get a resource containing a post's user.
     * 
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeUser(Post $post, $relations = [])
    {
        return (new UserSerializer($relations))->resource($post->user);
    }

    /**
     * Get a resource containing a post's discussion.
     * 
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeDiscussion(Post $post, $relations = [])
    {
        return (new DiscussionBasicSerializer($relations))->resource($post->discussion);
    }

    /**
     * Get a resource containing a post's edit user.
     * 
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeEditUser(Post $post, $relations = [])
    {
        return (new UserBasicSerializer($relations))->resource($post->editUser);
    }

    /**
     * Get a resource containing a post's delete user.
     * 
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeDeleteUser(Post $post, $relations = [])
    {
        return (new UserBasicSerializer($relations))->resource($post->deleteUser);
    }
}
