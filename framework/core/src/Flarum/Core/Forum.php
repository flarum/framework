<?php namespace Flarum\Core;

use Tobscure\Permissible\Permissible;

use Flarum\Core\Support\Exceptions\PermissionDeniedException;

class Forum extends Entity
{
    use Permissible;

    public static function boot()
    {
        parent::boot();

        static::grant(function ($grant, $user, $permission) {
            return app('flarum.permissions')->granted($user, $permission, 'forum');
        });
    }

    public function assertCan($user, $permission)
    {
        if (! $this->can($user, $permission)) {
            throw new PermissionDeniedException;
        }
    }
}
