<?php namespace Flarum\Core\Activity;

class StartedDiscussionActivity extends PostedActivity
{
    public static function getType()
    {
        return 'startedDiscussion';
    }
}
