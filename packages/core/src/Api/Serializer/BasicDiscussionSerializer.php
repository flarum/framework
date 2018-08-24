<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Discussion\Discussion;
use InvalidArgumentException;

class BasicDiscussionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'discussions';

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
            'slug'  => $discussion->slug,
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
