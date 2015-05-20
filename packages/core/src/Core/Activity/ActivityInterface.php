<?php namespace Flarum\Core\Activity;

interface ActivityInterface
{
    /**
     * Get the model that is the subject of this activity.
     *
     * @return \Flarum\Core\Models\Model
     */
    public function getSubject();

    /**
     * Get the time at which the activity occurred.
     *
     * @return mixed
     */
    public function getTime();

    /**
     * Get the serialized type of this activity.
     *
     * @return string
     */
    public static function getType();

    /**
     * Get the name of the model class for the subject of this activity.
     *
     * @return string
     */
    public static function getSubjectModel();
}
