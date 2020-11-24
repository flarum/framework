<?php


namespace Flarum\Nicknames;

use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;
use Flarum\User\User;

class IdOnlyUserSlugDriver implements SlugDriverInterface {

    public function toSlug(AbstractModel $instance): string
    {
        return $instance->id;
    }

    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return User::where('id', $slug)->whereVisibleTo($actor)->firstOrFail();
    }
}
