<?php namespace Flarum\Api\Serializers;

class ForumSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'forums';

    protected function id($forum)
    {
        return 1;
    }

    /**
     * Serialize attributes of a Forum model for JSON output.
     *
     * @param  Forum $forum The Forum model to serialize.
     * @return array
     */
    protected function attributes($forum)
    {
        $attributes = [
            'title' => $forum->title
        ];

        return $this->extendAttributes($forum, $attributes);
    }
}
