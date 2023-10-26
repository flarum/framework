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
use Tobscure\JsonApi\Relationship;

class TagSerializer extends AbstractSerializer
{
    protected $type = 'tags';

    public function __construct(
        protected SlugManager $slugManager
    ) {
    }

    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Tag)) {
            throw new InvalidArgumentException(
                $this::class.' can only serialize instances of '.Tag::class
            );
        }

        $attributes = [
            'name'               => $model->name,
            'description'        => $model->description,
            'slug'               => $this->slugManager->forResource(Tag::class)->toSlug($model),
            'color'              => $model->color,
            'backgroundUrl'      => $model->background_path,
            'backgroundMode'     => $model->background_mode,
            'icon'               => $model->icon,
            'discussionCount'    => (int) $model->discussion_count,
            'position'           => $model->position === null ? null : (int) $model->position,
            'defaultSort'        => $model->default_sort,
            'isChild'            => (bool) $model->parent_id,
            'isHidden'           => (bool) $model->is_hidden,
            'lastPostedAt'       => $this->formatDate($model->last_posted_at),
            'canStartDiscussion' => $this->actor->can('startDiscussion', $model),
            'canAddToDiscussion' => $this->actor->can('addToDiscussion', $model)
        ];

        if ($this->actor->isAdmin()) {
            $attributes['isRestricted'] = (bool) $model->is_restricted;
        }

        return $attributes;
    }

    protected function parent(Tag $tag): ?Relationship
    {
        return $this->hasOne($tag, self::class);
    }

    protected function children(Tag $tag): ?Relationship
    {
        return $this->hasMany($tag, self::class);
    }

    protected function lastPostedDiscussion(Tag $tag): ?Relationship
    {
        return $this->hasOne($tag, DiscussionSerializer::class);
    }
}
