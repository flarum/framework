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
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($discussion)
    {
        $attributes = parent::getDefaultAttributes($discussion) + [
            'commentCount'      => (int) $discussion->comment_count,
            'participantCount'  => (int) $discussion->participant_count,
            'createdAt'         => $this->formatDate($discussion->created_at),
            'lastPostedAt'      => $this->formatDate($discussion->last_posted_at),
            'lastPostNumber'    => (int) $discussion->last_post_number,
            'canReply'          => $this->user->can('reply', $discussion),
            'canRename'         => $this->user->can('rename', $discussion),
            'canDelete'         => $this->user->can('delete', $discussion),
            'canHide'           => $this->user->can('hide', $discussion)
        ];

        if ($discussion->hidden_at) {
            $attributes['isHidden'] = true;
            $attributes['hiddenAt'] = $this->formatDate($discussion->hidden_at);
        }

        Discussion::setStateUser($this->actor);

        if ($state = $discussion->state) {
            $attributes += [
                'lastReadAt' => $this->formatDate($state->last_read_at),
                'lastReadPostNumber' => (int) $state->last_read_post_number
            ];
        }

        return $attributes;
    }
}
