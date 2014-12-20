<?php namespace Flarum\Core\Discussions;

use Flarum\Core\Users\User;

class DiscussionRepository
{
    public function find($id)
    {
        return Discussion::find($id);
    }

    public function findOrFail($id, User $user = null)
    {
        $query = Discussion::query();
        
        if ($user !== null) {
            $query = $query->whereCanView($user);
        }

        return $query->findOrFail($id);
    }

    public function save(Discussion $discussion)
    {
        $discussion->assertValid();
        $discussion->save();
    }

    public function delete(Discussion $discussion)
    {
        $discussion->delete();
    }

    public function getState(Discussion $discussion, User $user)
    {
        return $discussion->stateFor($user);
    }

    public function saveState(DiscussionState $state)
    {
        $state->save();
    }
}
