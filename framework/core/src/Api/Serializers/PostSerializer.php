<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;

class PostSerializer extends PostBasicSerializer
{
    /**
     * Default relations to link.
     * @var array
     */
    protected $link = ['discussion'];

    /**
     * Default relations to include.
     * @var array
     */
    protected $include = ['user', 'editUser', 'hideUser'];

    /**
     * Serialize attributes of a Post model for JSON output.
     *
     * @param  Post  $post The Post model to serialize.
     * @return array
     */
    protected function attributes(Post $post)
    {
        $attributes = parent::attributes($post);
        $user = static::$actor->getUser();

        unset($attributes['content']);

        $canEdit = $post->can($user, 'edit');

        if ($post->type === 'comment') {
            $attributes['contentHtml'] = $post->content_html;
            if ($canEdit) {
                $attributes['content'] = $post->content;
            }
        } else {
            $attributes['content'] = json_encode($post->content);
        }

        if ($post->edit_time) {
            $attributes['editTime'] = $post->edit_time->toRFC3339String();
        }

        if ($post->hide_time) {
            $attributes['isHidden'] = true;
            $attributes['hideTime'] = $post->hide_time->toRFC3339String();
        }

        $attributes += [
            'canEdit'   => $canEdit,
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
     * Get a resource containing a post's hide user.
     *
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeHideUser(Post $post, $relations = [])
    {
        return (new UserBasicSerializer($relations))->resource($post->hideUser);
    }
}
