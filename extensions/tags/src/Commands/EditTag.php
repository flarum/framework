<?php namespace Flarum\Tags\Commands;

use Flarum\Core\Tags\Tag;
use Flarum\Core\Users\User;

class EditTag
{
    /**
     * The ID of the tag to edit.
     *
     * @var int
     */
    public $tagId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the tag.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $tagId The ID of the tag to edit.
     * @param User $actor The user performing the action.
     * @param array $data The attributes to update on the tag.
     */
    public function __construct($tagId, User $actor, array $data)
    {
        $this->tagId = $tagId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
