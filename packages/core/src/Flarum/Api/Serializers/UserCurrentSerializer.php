<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\User;
use Event;

use DateTime, DateTimeZone;

class UserCurrentSerializer extends UserSerializer {

	public function serialize(User $user)
	{
		$serialized = parent::serialize($user);

		// TODO: make UserCurrentSerializer and UserSerializer work better with guests
		if ($user->id)
		{
			$serialized += [
				'time_zone'        => $user->time_zone,
				'time_zone_offset' => with(new DateTimeZone($user->time_zone))->getOffset(new DateTime('now'))
				// other user preferences. probably mostly from external sources (e.g. flarum/web)
			];
		}

		Event::fire('flarum.api.serialize.user.current', [&$serialized]);

		return $serialized;
	}

}
