<?php namespace Flarum\Core\Activity;

use Flarum\Core\Entity;
use Illuminate\Support\Str;
use Auth;

class Activity extends Entity {

	protected $table = 'activity';

	public function getDates()
	{
		return ['time'];
	}

	public function fromUser()
	{
		return $this->belongsTo('Flarum\Core\Models\User', 'from_user_id');
	}

	public function permission($permission)
	{
		return User::current()->can($permission, 'activity', $this);
	}

	public function editable()
	{
		return $this->permission('edit');
	}

	public function deletable()
	{
		return $this->permission('delete');
	}

}
