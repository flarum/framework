<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use DateTime;
use Flarum\Core\User;
use Flarum\Database\AbstractModel;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property int $last_activity
 * @property int $duration
 * @property \Carbon\Carbon $sudo_expiry_time
 * @property string $csrf_token
 * @property \Flarum\Core\User|null $user
 */
class Session extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'sessions';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $dates = ['sudo_expiry_time'];

    /**
     * Generate a session.
     *
     * @param User|null $user
     * @param int $duration How long before the session will expire, in minutes.
     * @return static
     */
    public static function generate(User $user = null, $duration = 60)
    {
        $session = new static;

        $session->assign($user)
            ->regenerateId()
            ->renew()
            ->setDuration($duration);

        return $session->extend();
    }

    /**
     * Assign the session to a user.
     *
     * @param User|null $user
     * @return $this
     */
    public function assign(User $user = null)
    {
        $this->user_id = $user ? $user->id : null;

        return $this;
    }

    /**
     * Regenerate the session ID.
     *
     * @return $this
     */
    public function regenerateId()
    {
        $this->id = sha1(uniqid('', true).Str::random(25).microtime(true));
        $this->csrf_token = Str::random(40);

        return $this;
    }

    /**
     * @return $this
     */
    public function extend()
    {
        $this->last_activity = time();

        return $this;
    }

    /**
     * @return $this
     */
    public function renew()
    {
        $this->extend();
        $this->sudo_expiry_time = time() + 30 * 60;

        return $this;
    }

    /**
     * @param int $duration How long before the session will expire, in minutes.
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSudo()
    {
        return $this->sudo_expiry_time > new DateTime;
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
