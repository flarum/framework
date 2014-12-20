<?php namespace Flarum\Core\Groups;

use Flarum\Core\Entity;

class Group extends Entity {

	protected $table = 'groups';

	const ADMINISTRATOR_ID = 1;
    const GUEST_ID = 2;
    const MEMBER_ID = 3;

	public function users()
    {
        return $this->belongsToMany('Flarum\Core\Users\User', 'users_groups');
    }

}
