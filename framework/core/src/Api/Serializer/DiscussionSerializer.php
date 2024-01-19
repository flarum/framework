<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Discussion\Discussion;

class DiscussionSerializer extends BasicDiscussionSerializer
{
    /**
     * @param Discussion $model
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        $attributes = parent::getDefaultAttributes($model) + [
            'commentCount' => (int) $model->comment_count,
            'participantCount' => (int) $model->participant_count,
            'createdAt' => $this->formatDate($model->created_at),
            'lastPostedAt' => $this->formatDate($model->last_posted_at),
            'lastPostNumber' => (int) $model->last_post_number,
            'canReply' => $this->actor->can('reply', $model),
            'canRename' => $this->actor->can('rename', $model),
            'canDelete' => $this->actor->can('delete', $model),
            'canHide' => $this->actor->can('hide', $model)
        ];

        if ($model->hidden_at) {
            $attributes['isHidden'] = true;
            $attributes['hiddenAt'] = $this->formatDate($model->hidden_at);
        }

        Discussion::setStateUser($this->actor);

        if ($state = $model->state) {
            $attributes += [
                'lastReadAt' => $this->formatDate($state->last_read_at),
                'lastReadPostNumber' => (int) $state->last_read_post_number
            ];
        }

        return $attributes;
    }
}
