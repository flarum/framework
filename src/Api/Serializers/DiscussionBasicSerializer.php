<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\Discussion;

class DiscussionBasicSerializer extends BaseSerializer
{
    /**
     * The resource type.
     * @var string
     */
    protected $type = 'discussions';

    /**
     * Serialize attributes of a Discussion model for JSON output.
     *
     * @param Discussion $discussion The Discussion model to serialize.
     * @return array
     */
    protected function attributes(Discussion $discussion)
    {
        $attributes = [
            'id'    => (int) $discussion->id,
            'title' => $discussion->title,
        ];

        return $this->attributesEvent($discussion, $attributes);
    }

    /**
     * Get the URL templates where this resource and its related resources can
     * be accessed.
     *
     * @return array
     */
    protected function href()
    {
        $href = [
            'discussions' => $this->action('DiscussionsController@show', ['id' => '{discussions.id}']),
            'posts'       => $this->action('PostsController@indexForDiscussion', ['id' => '{discussions.id}'])
        ];

        return $this->hrefEvent($href);
    }
}
