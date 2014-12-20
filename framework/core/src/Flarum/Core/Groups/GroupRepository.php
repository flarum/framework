<?php namespace Flarum\Core\Repositories;

class GroupRepository
{
    
    public function save(Group $group)
    {
        $group->save();
    }

    public function delete(Group $group)
    {
        $group->delete();
    }
}
