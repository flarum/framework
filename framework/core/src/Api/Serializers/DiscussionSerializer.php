<?php namespace Flarum\Api\Serializers;

class DiscussionSerializer extends DiscussionBasicSerializer
{
    /**
     * Default relations to include.
     *
     * @var array
     */
    protected $include = ['startUser', 'lastUser'];

    /**
     * Serialize attributes of a Discussion model for JSON output.
     *
     * @param Discussion $discussion The Discussion model to serialize.
     * @return array
     */
    protected function attributes($discussion)
    {
        $attributes = parent::attributes($discussion);

        $user = static::$actor->getUser();
        $state = $discussion->stateFor($user);

        $attributes += [
            'commentsCount'  => (int) $discussion->comments_count,
            'startTime'      => $discussion->start_time->toRFC3339String(),
            'lastTime'       => $discussion->last_time ? $discussion->last_time->toRFC3339String() : null,
            'lastPostNumber' => $discussion->last_post_number,
            'canReply'       => $discussion->can($user, 'reply'),
            'canEdit'        => $discussion->can($user, 'edit'),
            'canDelete'      => $discussion->can($user, 'delete'),

            'readTime'       => $state && $state->read_time ? $state->read_time->toRFC3339String() : null,
            'readNumber'     => $state ? (int) $state->read_number : 0
        ];

        return $this->extendAttributes($discussion, $attributes);
    }
}
