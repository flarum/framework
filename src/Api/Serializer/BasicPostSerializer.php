<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use InvalidArgumentException;

class BasicPostSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'posts';

    /**
     * {@inheritdoc}
     *
     * @param \Flarum\Post\Post $post
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($post)
    {
        if (! ($post instanceof Post)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Post::class
            );
        }

        $attributes = [
            'number'      => (int) $post->number,
            'createdAt'   => $this->formatDate($post->created_at),
            'contentType' => $post->type
        ];

        if ($post instanceof CommentPost) {
            $attributes['contentHtml'] = $post->formatContent($this->request);
        } else {
            $attributes['content'] = $post->content;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function discussion($post)
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }
}
