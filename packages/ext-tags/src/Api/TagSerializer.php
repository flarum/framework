<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Api;

use Flarum\Api\Serializers\Serializer;

class TagSerializer extends Serializer
{
    protected $type = 'tags';

    protected function getDefaultAttributes($tag)
    {
        $attributes = [
            'name'               => $tag->name,
            'description'        => $tag->description,
            'slug'               => $tag->slug,
            'color'              => $tag->color,
            'backgroundUrl'      => $tag->background_path,
            'backgroundMode'     => $tag->background_mode,
            'iconUrl'            => $tag->icon_path,
            'discussionsCount'   => (int) $tag->discussions_count,
            'position'           => $tag->position === null ? null : (int) $tag->position,
            'defaultSort'        => $tag->default_sort,
            'isChild'            => (bool) $tag->parent_id,
            'isHidden'           => (bool) $tag->is_hidden,
            'lastTime'           => $tag->last_time ? $tag->last_time->toRFC3339String() : null,
            'canStartDiscussion' => $tag->can($this->actor, 'startDiscussion')
        ];

        if ($this->actor->isAdmin()) {
            $attributes['isRestricted'] = (bool) $tag->is_restricted;
        }

        return $attributes;
    }

    protected function parent()
    {
        return $this->hasOne('Flarum\Tags\Api\TagSerializer');
    }

    protected function lastDiscussion()
    {
        return $this->hasOne('Flarum\Api\Serializers\DiscussionSerializer');
    }
}
