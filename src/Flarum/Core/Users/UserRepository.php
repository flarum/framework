<?php namespace Flarum\Core\Users;

class UserRepository
{
    public function find($id)
    {
        return User::find($id);
    }

    public function findOrFail($id, $user = null)
    {
        $query = User::query();
        
        if ($user !== null) {
            $query = $query->whereCanView($user);
        }

        return $query->findOrFail($id);
    }

    public function save(User $user)
    {
        $user->assertValid();
        $user->save();
    }

    public function delete(User $user)
    {
        $user->delete();

        // do something with their posts/discussions?
    }

    public function attachGroup(User $user, $groupId)
    {
        $user->groups()->attach($groupId);
    }

    public function detachGroup(User $user, $groupId)
    {
        $user->groups()->detach($groupId);
    }

    public function syncGroups(User $user, $groupIds)
    {
        $user->groups()->sync($groupIds);
    }
}
