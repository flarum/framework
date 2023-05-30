<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use InvalidArgumentException;
use Tobscure\JsonApi\Relationship;

class BasicDiscussionSerializer extends AbstractSerializer
{
    protected $type = 'discussions';

    public function __construct(
        protected SlugManager $slugManager
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Discussion)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Discussion::class
            );
        }

        return [
            'title' => $model->title,
            'slug' =>  $this->slugManager->forResource(Discussion::class)->toSlug($model),
        ];
    }

    protected function user($discussion): ?Relationship
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }

    protected function firstPost($discussion): ?Relationship
    {
        return $this->hasOne($discussion, BasicPostSerializer::class);
    }

    protected function lastPostedUser($discussion): ?Relationship
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }

    protected function lastPost($discussion): ?Relationship
    {
        return $this->hasOne($discussion, BasicPostSerializer::class);
    }

    protected function posts($discussion): ?Relationship
    {
        return $this->hasMany($discussion, PostSerializer::class);
    }

    protected function mostRelevantPost($discussion): ?Relationship
    {
        return $this->hasOne($discussion, PostSerializer::class);
    }

    protected function hiddenUser($discussion): ?Relationship
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }
}
