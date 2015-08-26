<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializers;

class DiscussionBasicSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'discussions';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($discussion)
    {
        return [
            'title' => $discussion->title
        ];
    }

    /**
     * @return callable
     */
    protected function startUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    protected function startPost()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostBasicSerializer');
    }

    /**
     * @return callable
     */
    protected function lastUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    protected function lastPost()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostBasicSerializer');
    }

    /**
     * @return callable
     */
    protected function posts()
    {
        return $this->hasMany('Flarum\Api\Serializers\PostSerializer');
    }

    /**
     * @return callable
     */
    protected function relevantPosts()
    {
        return $this->hasMany('Flarum\Api\Serializers\PostBasicSerializer');
    }
}
