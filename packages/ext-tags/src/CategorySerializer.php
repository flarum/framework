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

    protected function attributes($category)
    {
        $attributes = [
            'title'            => $category->title,
            'description'      => $category->description,
            'slug'             => $category->slug,
            'color'            => $category->color,
            'discussionsCount' => (int) $category->discussions_count
        ];

        return $this->extendAttributes($category, $attributes);
    }
}
