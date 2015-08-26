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

use Flarum\Core\Discussions\Discussion;

class DiscussionSerializer extends DiscussionBasicSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($discussion)
    {
        $attributes = parent::getDefaultAttributes($discussion) + [
            'commentsCount'     => (int) $discussion->comments_count,
            'participantsCount' => (int) $discussion->participants_count,
            'startTime'         => $discussion->start_time->toRFC3339String(),
            'lastTime'          => $discussion->last_time ? $discussion->last_time->toRFC3339String() : null,
            'lastPostNumber'    => $discussion->last_post_number,
            'canReply'          => $discussion->can($this->actor, 'reply'),
            'canRename'         => $discussion->can($this->actor, 'rename'),
            'canDelete'         => $discussion->can($this->actor, 'delete')
        ];

        Discussion::setStateUser($this->actor);

        if ($state = $discussion->state) {
            $attributes += [
                'readTime'   => $state->read_time ? $state->read_time->toRFC3339String() : null,
                'readNumber' => (int) $state->read_number
            ];
        }

        return $attributes;
    }
}
