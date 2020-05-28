<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Post\CommentPost;

class PostSerializer extends BasicPostSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($post)
    {
        $attributes = parent::getDefaultAttributes($post);

        unset($attributes['content']);

        $canEdit = $this->actor->can('edit', $post);

        if ($post instanceof CommentPost) {
            if ($canEdit) {
                $attributes['content'] = $post->content;
            }
            if ($this->actor->can('viewIps', $post)) {
                $attributes['ipAddress'] = $post->ip_address;
            }
        } else {
            $attributes['content'] = $post->content;
        }

        if ($post->edited_at) {
            $attributes['editedAt'] = $this->formatDate($post->edited_at);
        }

        if ($post->hidden_at) {
            $attributes['isHidden'] = true;
            $attributes['hiddenAt'] = $this->formatDate($post->hidden_at);
        }

        $attributes += [
            'canEdit'   => $canEdit,
            'canDelete' => $this->actor->can('delete', $post),
            'canHide'   => $this->actor->can('hide', $post)
        ];

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($post)
    {
        return $this->hasOne($post, UserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function discussion($post)
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function editedUser($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function hiddenUser($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }
}
