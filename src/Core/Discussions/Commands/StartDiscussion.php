<?php namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Users\User;

class StartDiscussion
{
    /**
     * The user authoring the discussion.
     *
     * @var User
     */
    public $actor;

    /**
     * The discussion attributes.
     *
     * @var array
     */
    public $data;

    /**
     * @param User $actor The user authoring the discussion.
     * @param array $data The discussion attributes.
     */
    public function __construct(User $actor, array $data)
    {
        $this->actor = $actor;
        $this->data = $data;
    }
}
