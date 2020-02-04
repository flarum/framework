<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Discussion\Discussion;
use Flarum\User\Gate;

class DiscussionSerializer extends BasicDiscussionSerializer
{
    /**
     * @var \Flarum\User\Gate
     */
    protected $gate;

    /**
     * @param \Flarum\User\Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($discussion)
    {
        $gate = $this->gate->forUser($this->actor);

        $attributes = parent::getDefaultAttributes($discussion) + [
            'commentCount'      => (int) $discussion->comment_count,
            'participantCount'  => (int) $discussion->participant_count,
            'createdAt'         => $this->formatDate($discussion->created_at),
            'lastPostedAt'      => $this->formatDate($discussion->last_posted_at),
            'lastPostNumber'    => (int) $discussion->last_post_number,
            'canReply'          => $gate->allows('reply', $discussion),
            'canRename'         => $gate->allows('rename', $discussion),
            'canDelete'         => $gate->allows('delete', $discussion),
            'canHide'           => $gate->allows('hide', $discussion)
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
