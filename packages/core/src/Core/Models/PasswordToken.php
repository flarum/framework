<?php namespace Flarum\Core\Models;

class PasswordToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var boolean
     */
    public $incrementing = false;

    /**
     * Generate a reset token for the specified user.
     *
     * @param  int  $userId
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
     * Define the relationship with the owner of this reset token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User');
    }
}
