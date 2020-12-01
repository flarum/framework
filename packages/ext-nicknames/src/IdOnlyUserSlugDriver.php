<?php


namespace Flarum\Nicknames;

use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;
use Flarum\User\User;
use Flarum\User\UserRepository;

class IdOnlyUserSlugDriver implements SlugDriverInterface {
    /**
     * @var $users UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function toSlug(AbstractModel $instance): string
    {
        return $instance->id;
    }

    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return $this->users->findOrFail($slug, $actor);
    }
}
