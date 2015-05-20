<?php namespace Flarum\Core\Activity;

use Flarum\Core\Models\User;

class JoinedActivity extends ActivityAbstract
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getSubject()
    {
        return $this->user;
    }

    public function getTime()
    {
        return $this->user->join_time;
    }

    public static function getType()
    {
        return 'joined';
    }

    public static function getSubjectModel()
    {
        return 'Flarum\Core\Models\User';
    }
}
