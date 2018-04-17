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
            'commentsCount'     => (int) $discussion->comment_count,
            'participantsCount' => (int) $discussion->participant_count,
            'startTime'         => $this->formatDate($discussion->created_at),
            'lastTime'          => $this->formatDate($discussion->last_posted_at),
            'lastPostNumber'    => (int) $discussion->last_post_number,
            'canReply'          => $gate->allows('reply', $discussion),
            'canRename'         => $gate->allows('rename', $discussion),
            'canDelete'         => $gate->allows('delete', $discussion),
            'canHide'           => $gate->allows('hide', $discussion)
        ];

        if ($discussion->hidden_at) {
            $attributes['isHidden'] = true;
            $attributes['hideTime'] = $this->formatDate($discussion->hidden_at);
        }

        Discussion::setStateUser($this->actor);

        if ($state = $discussion->state) {
            $attributes += [
                'readTime'   => $this->formatDate($state->read_time),
                'readNumber' => (int) $state->read_number
            ];
        }

        return $attributes;
    }
}
