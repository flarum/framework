<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Posts\Post;

class PostBasicSerializer extends BaseSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'PostBasic';

    /**
     * The resource type.
     * @var string
     */
    protected $type = 'posts';

    /**
     * Default relations to link.
     * @var array
     */
    protected $link = ['discussion'];

    /**
     * Default relations to include.
     * @var array
     */
    protected $include = ['user'];

    /**
     * Serialize attributes of a Post model for JSON output.
     *
     * @param Post $post The Post model to serialize.
     * @return array
     */
    protected function attributes(Post $post)
    {
        $attributes = [
            'id'      => (int) $post->id,
            'number'  => (int) $post->number,
            'time'    => $post->time->toRFC3339String(),
            'type'    => $post->type
        ];

        if ($post->type === 'comment') {
            $attributes['content'] = str_limit($post->content, 200);
        } else {
            $attributes['content'] = json_encode($post->content);
        }

        return $this->attributesEvent($post, $attributes);
    }

    /**
     * Get the URL templates where this resource and its related resources can
     * be accessed.
     *
     * @return array
     */
    public function href()
    {
        return [
            'posts' => $this->action('PostsController@show', ['id' => '{posts.id}'])
        ];
    }

    /**
     * Get a resource containing a post's user.
     *
     * @param Post $post
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeUser(Post $post, $relations)
    {
        return (new UserBasicSerializer($relations))->resource($post->user);
    }

    /**
     * Get a resource containing a post's discussion ID.
     *
     * @param Post $post
     * @return Tobscure\JsonApi\Resource
     */
    public function linkDiscussion(Post $post)
    {
        return (new DiscussionBasicSerializer)->resource($post->discussion_id);
    }
}
