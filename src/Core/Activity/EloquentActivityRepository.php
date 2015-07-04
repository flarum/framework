<?php namespace Flarum\Core\Activity;

use Flarum\Core\Users\User;

class EloquentActivityRepository implements ActivityRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findByUser($userId, User $actor, $limit = null, $offset = 0, $type = null)
    {
        $query = Activity::where('user_id', $userId)
            ->whereIn('type', $this->getRegisteredTypes())
            ->latest('time')
            ->skip($offset)
            ->take($limit);

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Get a list of activity types that have been registered with the activity
     * model.
     *
     * @return array
     */
    protected function getRegisteredTypes()
    {
        return array_keys(Activity::getSubjectModels());
    }
}
