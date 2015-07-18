<?php namespace Flarum\Core\Support;

use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;
use Flarum\Events\ModelAllow;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * 'Lock' an object, allowing the permission of a user to perform an action to
 * be tested.
 */
trait Locked
{

}
