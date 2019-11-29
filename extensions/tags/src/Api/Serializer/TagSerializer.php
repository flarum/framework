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

class TagSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'tags';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($tag)
    {
        $attributes = [
            'name'               => $tag->name,
            'description'        => $tag->description,
            'slug'               => $tag->slug,
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

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastPostedDiscussion($tag)
    {
        return $this->hasOne($tag, DiscussionSerializer::class);
    }
}
