<?php namespace Flarum\Api\Serializers;

class DiscussionBasicSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'discussions';

    /**
     * Serialize attributes of a Discussion model for JSON output.
     *
     * @param Discussion $discussion The Discussion model to serialize.
     * @return array
     */
    protected function attributes($discussion)
    {
        $attributes = [
            'title' => $discussion->title
        ];

        if (count($discussion->removedPosts)) {
            $attributes['removedPosts'] = $discussion->removedPosts;
        }

        return $this->extendAttributes($discussion, $attributes);
    }

    public function startUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    public function startPost()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostBasicSerializer');
    }

    public function lastUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    public function lastPost()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostBasicSerializer');
    }

    public function posts()
    {
        return $this->hasMany('Flarum\Api\Serializers\PostSerializer');
    }

    public function relevantPosts()
    {
        return $this->hasMany('Flarum\Api\Serializers\PostBasicSerializer');
    }

    public function addedPosts()
    {
        return $this->hasMany('Flarum\Api\Serializers\PostSerializer');
    }
}
