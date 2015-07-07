<?php namespace Flarum\Core\Users;

use Flarum\Core\Model;

/**
 * @todo document database columns with @property
 */
class EmailToken extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'email_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an email token for the specified user.
     *
     * @param int $userId
     * @param string $email
     * @return static
     */
    public static function generate($userId, $email)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->email = $email;
        $token->created_at = time();

        return $token;
    }

    /**
     * Define the relationship with the owner of this email token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User');
    }
}
