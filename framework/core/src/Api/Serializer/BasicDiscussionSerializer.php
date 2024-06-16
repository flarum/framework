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

class BasicDiscussionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'discussions';

    /**
     * @var SlugManager
     */
    protected $slugManager;

    public function __construct(SlugManager $slugManager)
    {
        $this->slugManager = $slugManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param Discussion $discussion
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($discussion)
    {
        if (! ($discussion instanceof Discussion)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Discussion::class
            );
        }

        return [
            'title' => $discussion->title,
            'slug' => $this->slugManager->forResource(Discussion::class)->toSlug($discussion),
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($discussion)
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function firstPost($discussion)
    {
        return $this->hasOne($discussion, BasicPostSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastPostedUser($discussion)
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastPost($discussion)
    {
        return $this->hasOne($discussion, BasicPostSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function posts($discussion)
    {
        return $this->hasMany($discussion, PostSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function mostRelevantPost($discussion)
    {
        return $this->hasOne($discussion, PostSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function hiddenUser($discussion)
    {
        return $this->hasOne($discussion, BasicUserSerializer::class);
    }
}
