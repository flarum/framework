<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

/**
 * @property array $content
 */
abstract class AbstractEventPost extends Post
{
    /**
     * Unserialize the content attribute from the database's JSON value.
     *
     * @param string $value
     * @return array
     */
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Serialize the content attribute to be stored in the database as JSON.
     *
     * @param string $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
