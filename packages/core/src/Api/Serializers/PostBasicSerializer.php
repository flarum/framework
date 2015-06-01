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
            $attributes['excerpt'] = str_limit($post->contentPlain, 200);
        } else {
            $attributes['content'] = $post->content;
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
