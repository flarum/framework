<?php

namespace Flarum\Flags\Event;

use Flarum\Flags\Flag;
use Flarum\User\User;

// Separate from post events: listeners that assume a post remain compatible.
class UserFlagCreated
{
    public function __construct(public Flag $flag, public ?User $actor, public array $data = [])
    {
    }
}
