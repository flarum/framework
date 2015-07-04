<?php namespace Flarum\Core\Activity;

use Flarum\Core\Users\User;

/**
 * An activity blueprint for the 'joined' activity type, which represents a user
 * joining the forum.
 */
class JoinedBlueprint implements Blueprint
{
    /**
     * The user who joined the forum.
     *
     * @var User
     */
    protected $user;

    /**
     * Create a new 'joined' activity blueprint.
     *
     * @param User $user The user who joined the forum.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getTime()
    {
        return $this->user->join_time;
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'joined';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return 'Flarum\Core\Users\User';
    }
}
