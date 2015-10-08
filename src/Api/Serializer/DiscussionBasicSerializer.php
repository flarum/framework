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

use Flarum\Core\Discussion;
use InvalidArgumentException;

class DiscussionBasicSerializer extends AbstractSerializer
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
            throw new InvalidArgumentException(get_class($this)
                . ' can only serialize instances of ' . Discussion::class);
        }

        return [
            'title' => $discussion->title
        ];
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function startUser()
    {
        return $this->hasOne('Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function startPost()
    {
        return $this->hasOne('Flarum\Api\Serializer\PostBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function lastUser()
    {
        return $this->hasOne('Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function lastPost()
    {
        return $this->hasOne('Flarum\Api\Serializer\PostBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasManyBuilder
     */
    protected function posts()
    {
        return $this->hasMany('Flarum\Api\Serializer\PostSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasManyBuilder
     */
    protected function relevantPosts()
    {
        return $this->hasMany('Flarum\Api\Serializer\PostBasicSerializer');
    }
}
