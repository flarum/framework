<?php

namespace Flarum\Flags\Event;

use Flarum\Flags\Flag;
use Flarum\User\User;

class UserFlagsDeleting
{
    public function __construct(public Flag $flag, public User $actor, public array $data = [])
    {
    }
}
