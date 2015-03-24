<?php namespace Flarum\Api\Serializers;

class PostBasicSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'posts';

    /**
     * Default relations to link.
     *
     * @var array
     */
    protected $link = ['discussion'];

    /**
     * Default relations to include.
     *
     * @var array
     */
    protected $include = ['user'];

    /**
     * Serialize attributes of a Post model for JSON output.
     *
     * @param Post $post The Post model to serialize.
     * @return array
     */
    protected function attributes($post)
    {
        $attributes = [
            'id'      => (int) $post->id,
            'number'  => (int) $post->number,
            'time'    => $post->time->toRFC3339String(),
            'contentType'    => $post->type
        ];

        if ($post->type === 'comment') {
            $attributes['content'] = str_limit($post->content, 200);
        } else {
            $attributes['content'] = json_encode($post->content);
        }

        return $this->extendAttributes($post, $attributes);
    }

    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    public function discussion()
    {
        return $this->hasOne('Flarum\Api\Serializers\DiscussionBasicSerializer');
    }
}
