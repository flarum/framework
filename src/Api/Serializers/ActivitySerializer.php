<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\Activity;
use Event;

class ActivitySerializer extends BaseSerializer {
	
	public function serialize(Activity $activity)
	{
		$serialized = [
			'id' => (int) $activity->id
		];

		Event::fire('flarum.api.serialize.activity', [&$serialized]);

		return $serialized;
	}

}
