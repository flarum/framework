<?php namespace Flarum\Api\Serializers;

class PostSerializer extends PostBasicSerializer
{
    /**
     * Default relations to include.
     *
     * @var array
     */
    protected $include = ['user', 'editUser', 'hideUser'];

    /**
     * Serialize attributes of a Post model for JSON output.
     *
     * @param  Post  $post The Post model to serialize.
     * @return array
     */
    protected function attributes($post)
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
            $attributes['content'] = $post->content;
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

        return $this->extendAttributes($post, $attributes);
    }

    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }

    public function discussion()
    {
        return $this->hasOne('Flarum\Api\Serializers\DiscussionSerializer');
    }

    public function editUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }

    public function hideUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }
}
