<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\User;
use Event;

class UserAdminSerializer extends UserSerializer {

	public function serialize(User $user)
	{
		$serialized = parent::serialize($user);

		$serialized += [
			'email' => $user->email,
		];

		Event::fire('flarum.api.serialize.user.admin', [&$serialized]);

		return $serialized;
	}

}
