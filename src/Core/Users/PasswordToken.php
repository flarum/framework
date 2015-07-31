<?php namespace Flarum\Core\Users;

use Flarum\Core\Model;

/**
 * @todo document database columns with @property
 */
class PasswordToken extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'password_tokens';

    /**
     * {@inheritdoc}
     */
    public $dates = ['created_at'];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate a password token for the specified user.
     *
     * @param int $userId
     * @return static
     */
    public static function generate($userId)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->created_at = time();

        return $token;
    }

    /**
     * Define the relationship with the owner of this password token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User');
    }
}
