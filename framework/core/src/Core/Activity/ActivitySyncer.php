<?php namespace Flarum\Core\Activity;

/**
 * The Activity Syncer commits activity blueprints to the database. Where a
 * blueprint represents a single piece of activity, the syncer associates it
 * with a particular user(s) and makes it available on their activity feed.
 */
class ActivitySyncer
{
    /**
     * @var ActivityRepositoryInterface
     */
    protected $activity;

    /**
     * Create a new instance of the activity syncer.
     *
     * @param ActivityRepositoryInterface $activity
     */
    public function __construct(ActivityRepositoryInterface $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Sync a piece of activity so that it is present for the specified users,
     * and not present for anyone else.
     *
     * @param Blueprint $blueprint
     * @param \Flarum\Core\Models\User[] $users
     * @return void
     */
    public function sync(Blueprint $blueprint, array $users)
    {
        $attributes = $this->getAttributes($blueprint);

        // Find all existing activity records in the database matching this
        // blueprint. We will begin by assuming that they all need to be
        // deleted in order to match the provided list of users.
        $toDelete = Activity::where($attributes)->get();
        $toInsert = [];

        // For each of the provided users, check to see if they already have
        // an activity record in the database. If they do, we can leave it be;
        // otherwise, we will need to create a new one for them.
        foreach ($users as $user) {
            $existing = $toDelete->first(function ($activity) use ($user) {
                return $activity->user_id === $user->id;
            });

            if ($existing) {
                $toDelete->forget($toDelete->search($existing));
            } else {
                $toInsert[] = $attributes + ['user_id' => $user->id];
            }
        }

        // Finally, delete all of the remaining activity records which weren't
        // removed from this collection by the above loop. Insert the records
        // we need to insert as well.
        if (count($toDelete)) {
            $this->deleteActivity($toDelete->lists('id'));
        }

        if (count($toInsert)) {
            $this->createActivity($toInsert);
        }
    }

    /**
     * Delete a piece of activity for all users.
     *
     * @param Blueprint $blueprint
     * @return void
     */
    public function delete(Blueprint $blueprint)
    {
        Activity::where($this->getAttributes($blueprint))->delete();
    }

    /**
     * Delete a list of activity records.
     *
     * @param int[] $ids
     */
    protected function deleteActivity(array $ids)
    {
        Activity::whereIn('id', $ids)->delete();
    }

    /**
     * Insert a list of activity record into the database.
     *
     * @param array[] $records An array containing arrays of activity record
     *     attributes to insert.
     */
    protected function createActivity(array $records)
    {
        Activity::insert($records);
    }

    /**
     * Construct an array of attributes to be stored in an activity record in
     * the database, given an activity blueprint.
     *
     * @param Blueprint $blueprint
     * @return array
     */
    protected function getAttributes(Blueprint $blueprint)
    {
        return [
            'type'       => $blueprint::getType(),
            'subject_id' => $blueprint->getSubject()->id,
            'time'       => $blueprint->getTime()
        ];
    }
}
