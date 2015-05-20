<?php namespace Flarum\Core\Activity;

use Flarum\Core\Repositories\ActivityRepositoryInterface;
use Flarum\Core\Models\Activity;

class ActivitySyncer
{
    protected $activity;

    public function __construct(ActivityRepositoryInterface $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Sync a piece of activity so that it is present for the specified users,
     * and not present for anyone else.
     *
     * @param \Flarum\Core\Activity\ActivityInterface $activity
     * @param \Flarum\Core\Models\User[] $users
     * @return void
     */
    public function sync(ActivityInterface $activity, array $users)
    {
        Activity::unguard();

        $attributes = [
            'type'       => $activity::getType(),
            'subject_id' => $activity->getSubject()->id,
            'time'       => $activity->getTime()
        ];

        $toDelete = Activity::where($attributes)->get();
        $toInsert = [];

        foreach ($users as $user) {
            $existing = $toDelete->where('user_id', $user->id)->first();

            if ($k = $toDelete->search($existing)) {
                $toDelete->pull($k);
            } else {
                $toInsert[] = $attributes + ['user_id' => $user->id];
            }
        }

        if (count($toDelete)) {
            Activity::whereIn('id', $toDelete->lists('id'))->delete();
        }
        if (count($toInsert)) {
            Activity::insert($toInsert);
        }
    }
}
