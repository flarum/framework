<?php namespace Flarum\Tags;

use Flarum\Api\Serializers\BaseSerializer;

class TagSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'tags';

    /**
     * Serialize tag attributes to be exposed in the API.
     *
     * @param \Flarum\Tags\Tag $tag
     * @return array
     */
    protected function attributes($tag)
    {
        $attributes = [
            'name'             => $tag->name,
            'description'      => $tag->description,
            'slug'             => $tag->slug,
            'color'            => $tag->color,
            'backgroundUrl'    => $tag->background_path,
            'iconUrl'          => $tag->icon_path,
            'discussionsCount' => (int) $tag->discussions_count,
            'position'         => $tag->position === null ? null : (int) $tag->position,
            'defaultSort'      => $tag->default_sort,
            'isChild'          => (bool) $tag->parent_id
        ];

        return $this->extendAttributes($tag, $attributes);
    }

    protected function parent()
    {
        return $this->hasOne('Flarum\Tags\TagSerializer');
    }
}
