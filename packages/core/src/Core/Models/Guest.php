<?php namespace Flarum\Core\Models;

class Guest extends User
{
    public $id = 0;

    /**
     * Return an array containing the 'guests' group model.
     *
     * @return \Flarum\Core\Models\Group
     */
    public function getGroupsAttribute()
    {
        if (! isset($this->attributes['groups'])) {
            $this->attributes['groups'] = $this->relations['groups'] = Group::where('id', Group::GUEST_ID)->get();
        }

        return $this->attributes['groups'];
    }

    /**
     * Check whether or not the user is a guest.
     *
     * @return boolean
     */
    public function guest()
    {
        return true;
    }
}
