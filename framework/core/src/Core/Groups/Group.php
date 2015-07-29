<?php namespace Flarum\Core\Groups;

use Flarum\Core\Model;

/**
 * @todo document database columns with @property
 */
class Group extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'groups';

    /**
     * The ID of the administrator group.
     */
    const ADMINISTRATOR_ID = 1;

    /**
     * The ID of the guest group.
     */
    const GUEST_ID = 2;

    /**
     * The ID of the member group.
     */
    const MEMBER_ID = 3;

    /**
     * Define the relationship with the group's users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('Flarum\Core\Users\User', 'users_groups');
    }

    /**
     * Define the relationship with the group's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->hasMany('Flarum\Core\Groups\Permission');
    }
}
