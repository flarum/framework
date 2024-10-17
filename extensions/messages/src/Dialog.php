<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int|null $first_message_id
 * @property int|null $last_message_id
 * @property \Carbon\Carbon|null $last_message_at
 * @property int|null $last_message_user_id
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DialogMessage> $messages
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read DialogMessage|null $firstMessage
 * @property-read DialogMessage|null $lastMessage
 * @property-read User|null $lastMessageUser
 * @property-read UserDialogState|null $state
 */
class Dialog extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    protected $table = 'dialogs';

    public $timestamps = true;

    public static array $types = ['direct'];

    protected $guarded = [];

    protected static ?User $stateUser = null;

    public function messages(): HasMany
    {
        return $this->hasMany(DialogMessage::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dialog_user');
    }

    public function firstMessage(): BelongsTo
    {
        return $this->belongsTo(DialogMessage::class, 'first_message_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(DialogMessage::class, 'last_message_id');
    }

    public function lastMessageUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_user_id');
    }

    public function state(?User $user = null): HasOne
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne(UserDialogState::class)->where('user_id', $user?->id);
    }

    public function setFirstMessage(DialogMessage $message): static
    {
        $this->created_at = $message->created_at;
        $this->first_message_id = $message->id;

        return $this;
    }

    public function setLastMessage(DialogMessage $message): static
    {
        $this->last_message_at = $message->created_at;
        $this->last_message_user_id = $message->user_id;
        $this->last_message_id = $message->id;

        return $this;
    }

    public static function for(DialogMessage $model, array $users): self
    {
        $otherUserId = array_values(array_diff($users, [$model->user_id]))[0] ?? null;

        if (! $otherUserId) {
            throw new InvalidArgumentException('Dialog must have at least two users');
        }

        return self::query()
            ->whereRelation('users', 'user_id', $model->user_id)
            ->whereRelation('users', 'user_id', $otherUserId)
            ->firstOrCreate([
                'type' => 'direct',
            ]);
    }

    public function recipient(?User $actor): ?User
    {
        return $this->users->first(fn (User $user) => $user->id !== $actor?->id);
    }

    public static function setStateUser(User $user): void
    {
        static::$stateUser = $user;
    }
}
