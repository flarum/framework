<?php


namespace Flarum\User;


use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;

class UsernameSlugDriver implements SlugDriverInterface
{

    public function toSlug(AbstractModel $instance): string
    {
        return $instance->username;
    }

    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return User::where('username', $slug)->whereVisibleTo($actor)->firstOrFail();
    }
}
