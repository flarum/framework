<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Discussions\DiscussionState;

class DiscussionSerializer extends DiscussionBasicSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'Discussion';

    /**
     * Default relations to include.
     * @var array
     */
    protected $include = ['startUser', 'lastUser'];

    /**
     * Serialize attributes of a Discussion model for JSON output.
     * 
     * @param Discussion $discussion The Discussion model to serialize.
     * @return array
     */
    protected function attributes(Discussion $discussion)
    {
        $attributes = parent::attributes($discussion);

        $attributes += [
            'commentsCount'  => (int) $discussion->comments_count,
            'startTime'      => $discussion->start_time->toRFC3339String(),
            'lastTime'       => $discussion->last_time ? $discussion->last_time->toRFC3339String() : null,
            'lastPostNumber' => $discussion->last_post_number,
            'canEdit'        => $discussion->permission('edit'),
            'canDelete'      => $discussion->permission('delete')
        ];

        if ($state = $discussion->state) {
            $attributes += [
                'readTime'   => $state->read_time ? $state->read_time->toRFC3339String() : null,
                'readNumber' => (int) $state->read_number
            ];
        }

        return $this->attributesEvent($discussion, $attributes);
    }

    /**
     * Get a collection containing a discussion's viewable post IDs.
     * 
     * @param Discussion $discussion
     * @return Tobscure\JsonApi\Collection
     */
    public function linkPosts(Discussion $discussion)
    {
        return (new PostBasicSerializer)->collection($discussion->posts()->whereCanView()->ids());
    }

    /**
     * Get a collection containing a discussion's viewable posts.
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Collection
     */
    public function includePosts(Discussion $discussion, $relations)
    {
        return (new PostSerializer($relations))->collection($discussion->posts()->whereCanView()->get());
    }

    /**
     * Get a collection containing a discussion's relevant posts. Assumes that
     * the discussion model's relevantPosts attributes has been filled (this
     * happens in the DiscussionFinder.)
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Collection
     */
    public function includeRelevantPosts(Discussion $discussion, $relations)
    {
        return (new PostBasicSerializer($relations))->collection($discussion->relevantPosts);
    }

    /**
     * Get a resource containing a discussion's start user.
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeStartUser(Discussion $discussion, $relations)
    {
        return (new UserBasicSerializer($relations))->resource($discussion->startUser);
    }

    /**
     * Get a resource containing a discussion's starting post.
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeStartPost(Discussion $discussion, $relations)
    {
        return (new PostBasicSerializer($relations))->resource($discussion->startPost);
    }

    /**
     * Get a resource containing a discussion's last user.
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeLastUser(Discussion $discussion, $relations)
    {
        return (new UserBasicSerializer($relations))->resource($discussion->lastUser);
    }

    /**
     * Get a resource containing a discussion's last post.
     * 
     * @param Discussion $discussion
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeLastPost(Discussion $discussion, $relations)
    {
        return (new PostBasicSerializer($relations))->resource($discussion->lastPost);
    }
}
