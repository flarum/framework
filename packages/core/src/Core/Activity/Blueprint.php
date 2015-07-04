<?php namespace Flarum\Core\Activity;

/**
 * An activity Blueprint, when instantiated, represents a single piece of
 * activity. The blueprint is used by the ActivitySyncer to commit the activity
 * to the database.
 */
interface Blueprint
{
    /**
     * Get the model that is the subject of this activity.
     *
     * @return \Flarum\Core\Model
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
