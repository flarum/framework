<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Http\SlugManager;
use Flarum\Tags\Tag;
use InvalidArgumentException;

class TagSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'tags';

    /**
     * @var SlugManager
     */
    protected $slugManager;

    public function __construct(SlugManager $slugManager)
    {
        $this->slugManager = $slugManager;
    }

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param Tag $tag
     * @return array
     */
    protected function getDefaultAttributes($tag)
    {
        if (! ($tag instanceof Tag)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Tag::class
            );
        }

        $attributes = [
            'name'               => $tag->name,
            'description'        => $tag->description,
            'slug'               => $this->slugManager->forResource(Tag::class)->toSlug($tag),
            'color'              => $tag->color,
            'backgroundUrl'      => $tag->background_path,
            'backgroundMode'     => $tag->background_mode,
            'icon'               => $tag->icon,
            'discussionCount'    => (int) $tag->discussion_count,
            'position'           => $tag->position === null ? null : (int) $tag->position,
            'defaultSort'        => $tag->default_sort,
            'isChild'            => (bool) $tag->parent_id,
            'isHidden'           => (bool) $tag->is_hidden,
            'lastPostedAt'       => $this->formatDate($tag->last_posted_at),
            'canStartDiscussion' => $this->actor->can('startDiscussion', $tag),
            'canAddToDiscussion' => $this->actor->can('addToDiscussion', $tag)
        ];

        if ($this->actor->isAdmin()) {
            $attributes['isRestricted'] = (bool) $tag->is_restricted;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function parent($tag)
    {
        return $this->hasOne($tag, self::class);
    }

    protected function children($tag)
    {
        return $this->hasMany($tag, self::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastPostedDiscussion($tag)
    {
        return $this->hasOne($tag, DiscussionSerializer::class);
    }
}
