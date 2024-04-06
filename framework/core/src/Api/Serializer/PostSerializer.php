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
use Tobscure\JsonApi\Relationship;

class PostSerializer extends BasicPostSerializer
{
    /**
     * @param Post $model
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        $attributes = parent::getDefaultAttributes($model);

        unset($attributes['content']);

        $canEdit = $this->actor->can('edit', $model);

        if ($model instanceof CommentPost) {
            if ($canEdit) {
                $attributes['content'] = $model->content;
            }
            if ($this->actor->can('viewIps', $model)) {
                $attributes['ipAddress'] = $model->ip_address;
            }
        } else {
            $attributes['content'] = $model->content;
        }

        if ($model->edited_at) {
            $attributes['editedAt'] = $this->formatDate($model->edited_at);
        }

        if ($model->hidden_at) {
            $attributes['isHidden'] = true;
            $attributes['hiddenAt'] = $this->formatDate($model->hidden_at);
        }

        $attributes += [
            'canEdit' => $canEdit,
            'canDelete' => $this->actor->can('delete', $model),
            'canHide' => $this->actor->can('hide', $model)
        ];

        return $attributes;
    }

    protected function user(Post $post): ?Relationship
    {
        return $this->hasOne($post, UserSerializer::class);
    }

    protected function discussion(Post $post): ?Relationship
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }

    protected function editedUser(Post $post): ?Relationship
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    protected function hiddenUser(Post $post): ?Relationship
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }
}
