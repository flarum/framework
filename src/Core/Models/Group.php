<?php namespace Flarum\Core\Models;

class Group extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'groups';

    /**
     * The ID of the administrator group.
     *
     * @var int
     */
    const ADMINISTRATOR_ID = 1;

    /**
     * The ID of the guest group.
     *
     * @var int
     */
    const GUEST_ID = 2;

    /**
     * The ID of the member group.
     *
     * @var int
     */
    const MEMBER_ID = 3;

    /**
     * Define the relationship with the group's users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('Flarum\Core\Models\User', 'users_groups');
    }
}
