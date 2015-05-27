<?php namespace Flarum\Core\Models;

class AccessToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'access_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var boolean
     */
    public $incrementing = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'expires_at'];

    /**
     * Generate an access token for the specified user.
     *
     * @param  int  $userId
     * @param  int  $minutes
     * @return static
     */
    public static function generate($userId, $minutes = 60)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->created_at = time();
        $token->expires_at = time() + $minutes * 60;

        return $token;
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User');
    }
}
