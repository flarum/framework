<?php namespace Flarum\Core\Users;

use Flarum\Core\Groups\Group;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;
use Auth;
use DB;

class Guest extends User {

	public function __construct(array $attributes = array())
	{
		// Guest has an ID of 0.
		// $this->setAttribute($this->getKeyName(), 0);

		return parent::__construct($attributes);
	}

	public function getGroupsAttribute()
	{
		if ( ! isset($this->attributes['groups']))
		{
			$this->attributes['groups'] = $this->relations['groups'] = Group::where('id', Group::GUEST_ID)->get();
		}

		return $this->attributes['groups'];
	}

	public function guest()
	{
		return true;
	}

}
