<?php namespace Flarum\Categories;

use Flarum\Api\Serializers\BaseSerializer;

class CategorySerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'categories';

    /**
     * Serialize category attributes to be exposed in the API.
     *
     * @param \Flarum\Categories\Category $category
     * @return array
     */
    protected function attributes($category)
    {
        $attributes = [
            'title'            => $category->title,
            'description'      => $category->description,
            'slug'             => $category->slug,
            'color'            => $category->color,
            'discussionsCount' => (int) $category->discussions_count,
            'position'         => (int) $category->position
        ];

        return $this->extendAttributes($category, $attributes);
    }
}
