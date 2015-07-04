<?php namespace Flarum\Core\Activity;

/**
 * An activity blueprint for the 'startedDiscussion' activity type, which
 * represents a user starting a discussion.
 */
class StartedDiscussionBlueprint extends PostedBlueprint
{
    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'startedDiscussion';
    }
}
